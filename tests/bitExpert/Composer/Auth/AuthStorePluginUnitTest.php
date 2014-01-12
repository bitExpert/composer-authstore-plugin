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

require __DIR__ . '/../../../../vendor/composer/composer/tests/Composer/TestCase.php';

/**
 * Unit test for {@link \bitExpert\Composer\Auth\AuthStorePlugin}.
 *
 * @covers bitExpert\Composer\Auth\AuthStorePlugin
 */
class AuthStorePluginUnitTest extends \Composer\TestCase
{
    const HOME = 'composer_home';
    /**
     * @var \org\bovigo\vfs\vfsStream\vfsStreamDirectory
     */
    private $root;
    /**
     * @var \Composer\IO\IOInterface
     */
    private $io;
    /**
     * @var \Composer\Composer
     */
    private $composer;
    /**
     * @var \bitExpert\Composer\Auth\AuthPlugin
     */
    private $authPlugin;

    /**
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        $this->root = \org\bovigo\vfs\vfsStream::setup(self::HOME);
        $this->io = $this->getMock('\Composer\IO\IOInterface');
        $this->composer = $this->getMock('\Composer\Composer');

        $this->authPlugin = new AuthStorePlugin();
    }

    /**
     * @test
     * @covers \bitExpert\Composer\Auth\AuthStorePlugin::activate
     */
    public function emptyAuthFileWillBeIgnored()
    {
        $this->createEmptyAuthFile(\org\bovigo\vfs\vfsStream::url(self::HOME));

        $this->io->expects($this->never())
            ->method('setAuthentication');

        $this->authPlugin->activate($this->composer, $this->io);
    }

    /**
     * @test
     * @covers \bitExpert\Composer\Auth\AuthStorePlugin::activate
     */
    public function readingAuthFileAndPassingTheCredentialsToIOInterface()
    {
        $this->createAuthFileWithCredentials(\org\bovigo\vfs\vfsStream::url(self::HOME));

        $this->io->expects($this->exactly(3))
            ->method('setAuthentication');

        $this->authPlugin->activate($this->composer, $this->io);
    }

    /**
     * Helper method for creating an empty auth.json file in the given $dir.
     *
     * @param string $dir
     */
    protected function createEmptyAuthFile($dir)
    {
        // build empty auth file and store it in the given $dir
        $authFileContent = array(
            'config' => array()
        );
        file_put_contents($dir . '/auth.json', json_encode($authFileContent));

        // expose the given $dir as environment variable so Composer will
        // pick it up
        putenv('COMPOSER_HOME=' . $dir);
    }

    /**
     * Helper method for creating a sample auth.json file in the given $dir.
     *
     * @param string $dir
     */
    protected function createAuthFileWithCredentials($dir)
    {
        // build sample auth file and store it in the given $dir
        $authFileContent = array(
            'config' => array(
                'basic-auth' => array(
                    'satis.loc' => array(
                        'username' => 'my_satis_user',
                        'password' => 'my_satis_pass'
                    )
                )
            )
        );
        file_put_contents($dir . '/auth.json', json_encode($authFileContent));

        // expose the given $dir as environment variable so Composer will
        // pick it up
        putenv('COMPOSER_HOME=' . $dir);
    }
}