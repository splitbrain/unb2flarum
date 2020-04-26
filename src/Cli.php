<?php

namespace splitbrain\unb2flarum;

use splitbrain\phpcli\Options;
use splitbrain\phpcli\PSR3CLI;
use splitbrain\unb2flarum\import\Categories;
use splitbrain\unb2flarum\import\Posts;
use splitbrain\unb2flarum\import\Threads;
use splitbrain\unb2flarum\import\Users;

class Cli extends PSR3CLI
{

    /**
     * @inheritDoc
     */
    protected function setup(Options $options)
    {
        $options->setHelp('Imports UNB to Flarum');
        $options->registerOption('config', 'Configuration file', 'c', 'config');
    }

    /**
     * @inheritDoc
     */
    protected function main(Options $options)
    {
        $db = new Db($this->getConfig($options));

        // run the importers. order does matter!
        (new Categories($db,$this))->import();
        (new Users($db,$this))->import();
        (new Threads($db,$this))->import();
        (new Posts($db,$this))->import();
    }

    /**
     * @param Options $options
     * @return array
     */
    protected function getConfig(Options $options)
    {
        $path = __DIR__ . '/../config.php';
        $path = $options->getOpt('config', $path);
        if(!file_exists($path)) {
            $this->fatal("Couldn't find config file $path");
        }

        return include $path;
    }
}
