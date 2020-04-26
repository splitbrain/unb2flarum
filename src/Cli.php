<?php

namespace splitbrain\unb2flarum;

use splitbrain\phpcli\Options;
use splitbrain\phpcli\PSR3CLI;
use splitbrain\unb2flarum\import\Categories;
use splitbrain\unb2flarum\import\Groups;
use splitbrain\unb2flarum\import\Posts;
use splitbrain\unb2flarum\import\Statistics;
use splitbrain\unb2flarum\import\Threads;
use splitbrain\unb2flarum\import\Users;
use splitbrain\unb2flarum\import\Watches;

class Cli extends PSR3CLI
{

    /**
     * @inheritDoc
     */
    protected function setup(Options $options)
    {
        $options->setHelp('Imports UNB to Flarum');
        $options->registerOption('config', 'Configuration file', 'c', 'config');
        $options->registerOption('test', 'Test-Run, do not commit the transaction at the end', 't');
    }

    /**
     * @inheritDoc
     */
    protected function main(Options $options)
    {
        $db = new Db($this->getConfig($options));

        // run the importers. order does matter!
        (new Categories($db, $this))->import();
        (new Users($db, $this))->import();
        (new Groups($db, $this))->import();
        (new Threads($db, $this))->import();
        (new Posts($db, $this))->import();
        (new Statistics($db, $this))->import();
        (new Watches($db, $this))->import();

        $this->success('Imports valid.');
        if ($options->getOpt('test')) {
            $this->notice('Skipping database commit.');
        } else {
            $this->notice('Committing to database...');
            $db->commit();
        }
        $this->success('Import done.');
    }

    /**
     * @param Options $options
     * @return array
     */
    protected function getConfig(Options $options)
    {
        $path = __DIR__ . '/../config.php';
        $path = $options->getOpt('config', $path);
        if (!file_exists($path)) {
            $this->fatal("Couldn't find config file $path");
        }

        return include $path;
    }
}
