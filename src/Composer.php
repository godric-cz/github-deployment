<?php

namespace Godric\GithubDeployment;

use Composer\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * Wrapper for calling `composer install` directly from php
 */
class Composer {

    static function installTo($target) {
        // skip, if composer is not used in target
        if(!is_file($target . '/composer.json')) return;

        $composerHome = $target . '/vendor/composer_home';
        if(!is_dir($composerHome)) mkdir($composerHome);

        // Composer\Factory::getHomeDir() method
        // needs COMPOSER_HOME environment variable set
        putenv('COMPOSER_HOME=' . $composerHome);
        // TODO probably not true, see https://getcomposer.org/doc/03-cli.md#composer-home and https://stackoverflow.com/questions/17219436/run-composer-with-a-php-script-in-browser
        // but not setting this causes some update issues

        $oldCwd = getcwd();
        chdir($target);

        // call `composer install` command programmatically
        echo "installing composer\n";
        $input = new ArrayInput(array('command' => 'install'));
        $application = new Application();
        $application->setAutoExit(false); // prevent `$application->run` method from exitting the script
        $application->run($input);
        echo "done\n";

        chdir($oldCwd);
    }

}
