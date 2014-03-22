<?php

/*
 * This file is part of the Composer AuthStore Plugin.
 *
 * (c) bitExpert AG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace bitExpert\Composer\Auth;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Json\JsonFile;
use Composer\Plugin\PluginInterface;

/**
 * Composer AuthStore Plugin. The plugin will read an auth.json file from the COMPOSER_HOME directory and
 * push the stored credentials into the given IOInterface.
 *
 * @author Stephan Hochdörfer
 */
class AuthStorePlugin implements PluginInterface
{
    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * @var Composer
     */
    protected $composer;

    /**
     * {@inheritDoc}
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;

        // setting the authentication credentials here as the onPreFileDownload event
        // does not get fired when accessing the packages.json file of a satis repository.
        $authConfig = $this->getAuthConfig();
        foreach ($authConfig as $host => $credentials) {

            if (!isset($credentials['username'])) {
                unset($this->authConfig[$host]);
                continue;
            }

            if (!isset($credentials['password'])) {
                $credentials['password'] = null;
            }

            // set the authentication credentials for each host
            $this->io->setAuthentication($host, $credentials['username'], $credentials['password']);

            // for a satis repository we need to explicitly add the authentication
            // information for the packages.json file. Otherwise the user still needs
            // to enter the credentials.
            $this->io->setAuthentication('http://' . $host . '/packages.json', $credentials['username'], $credentials['password']);
            $this->io->setAuthentication('https://' . $host . '/packages.json', $credentials['username'], $credentials['password']);
        }
    }

    /**
     * Returns basic auth config.
     * It consists in an aggregation of auth.json from COMPOSER_HOME and configuration from local auth.json
     * under "config" key.
     *
     * Local config always has precedence.
     *
     * @return array
     */
    protected function getAuthConfig()
    {
        $localConfig = $this->readAuthConfig(getcwd() . '/auth.json');
        $globalConfig = $this->readAuthConfig($this->getHomeDir() . '/auth.json');
        return $localConfig + $globalConfig;
    }

    /**
     * Reads auth config from given json file path and returns it.
     *
     * @param string $authFilePath
     *
     * @return array
     */
    private function readAuthConfig( $authFilePath )
    {
        $file = new JsonFile($authFilePath);
        if ($file->exists()) {
            $auth = $file->read();
            if (isset($auth['config']['basic-auth'])) {
                return $auth['config']['basic-auth'];
            }
        }

        return array();
    }

    /**
     * Returns the Composer Home directory. The logic is "borrowed" from the implementation in
     * \Composer\Factory.
     *
     * @throws \RuntimeException
     * @return string
     */
    protected function getHomeDir()
    {
        $home = getenv('COMPOSER_HOME');
        if (!$home) {
            if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
                if (!getenv('APPDATA')) {
                    throw new \RuntimeException('The APPDATA or COMPOSER_HOME environment variable must be set for composer to run correctly');
                }
                $home = strtr(getenv('APPDATA'), '\\', '/') . '/Composer';
            } else {
                if (!getenv('HOME')) {
                    throw new \RuntimeException('The HOME or COMPOSER_HOME environment variable must be set for composer to run correctly');
                }
                $home = rtrim(getenv('HOME'), '/') . '/.composer';
            }
        }

        return $home;
    }
}
