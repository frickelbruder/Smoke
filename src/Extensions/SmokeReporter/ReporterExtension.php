<?php

namespace whm\Smoke\Extensions\SmokeReporter;

use PhmLabs\Components\Init\Init;
use Symfony\Component\Console\Output\OutputInterface;
use whm\Smoke\Config\Configuration;
use whm\Smoke\Extensions\SmokeReporter\Reporter\ConfigAwareReporter;
use whm\Smoke\Extensions\SmokeReporter\Reporter\OutputAwareReporter;
use whm\Smoke\Extensions\SmokeResponseRetriever\Retriever\Retriever;
use whm\Smoke\Scanner\Result;

class ReporterExtension
{
    private $reporters = array();
    private $output;
    private $config;

    public function init(OutputInterface $_output, Configuration $_configuration)
    {
        $this->output = $_output;
        $this->config = $_configuration;

        if ($_configuration->hasSection('reporter')) {
            $this->reporters = Init::initializeAll($_configuration->getSection('reporter'));
        }

        foreach ($this->reporters as $reporter) {
            if ($reporter instanceof OutputAwareReporter) {
                if (is_null($this->output)) {
                    throw new \RuntimeException('You tried to initialize an OutputAwareReporter ("' . get_class($reporter) . '") but did not set the OutputInterface');
                }
                $reporter->setOutput($this->output);
            }

            if ($reporter instanceof ConfigAwareReporter) {
                $reporter->setConfig($_configuration);
            }
        }
    }

    /**
     * @Event("Scanner.Init.ResponseRetriever")
     */
    public function getResponseRetriever(Retriever $responseRetriever)
    {
        foreach ($this->reporters as $reporter) {
            if (method_exists($reporter, 'setResponseRetriever')) {
                $reporter->setResponseRetriever($responseRetriever);
            }
        }
    }

    /**
     * @Event("Scanner.Scan.Validate")
     */
    public function process(Result $result)
    {
        foreach ($this->reporters as $reporter) {
            $reporter->processResult($result);
        }
    }

    /**
     * @Event("Scanner.Scan.Finish")
     */
    public function finish()
    {
        foreach ($this->reporters as $reporter) {
            $reporter->finish();
        }
    }
}
