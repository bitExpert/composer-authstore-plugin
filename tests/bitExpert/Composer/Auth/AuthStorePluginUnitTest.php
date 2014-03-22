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

use org\bovigo\vfs\vfsStream;

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
     * @var \Composer\IO\IOInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $io;
    /**
     * @var \Composer\Composer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $composer;
    /**
     * @var AuthStorePlugin
     */
    private $authPlugin;

    /**
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        parent::setUp();
        $this->root = vfsStream::setup(self::HOME);
        $this->io = $this->getMock('\Composer\IO\IOInterface');
        $this->composer = $this->getMock('\Composer\Composer');

        $this->authPlugin = new AuthStorePlugin();
    }

    protected function tearDown()
    {
        @unlink(getcwd() . '/auth.json');
        parent::tearDown();
    }

    /**
     * @test
     */
    public function emptyAuthFileWillBeIgnored()
    {
        $homeDir = vfsStream::url(self::HOME);
        $this->createEmptyAuthFile($homeDir);
        // expose the given $dir as environment variable so Composer will pick it up
        putenv('COMPOSER_HOME=' . $homeDir);

        $this->io->expects($this->never())
            ->method('setAuthentication');

        $this->authPlugin->activate($this->composer, $this->io);
    }

    /**
     * @test
     */
    public function readingAuthFileAndPassingTheCredentialsToIOInterface()
    {
        $credentials = array(
            'satis.loc' => array(
                'username' => 'my_satis_user',
                'password' => 'my_satis_pass'
            )
        );
        $homeDir = vfsStream::url(self::HOME);
        $this->createAuthFileWithCredentials($homeDir, $credentials);
        // expose the given $dir as environment variable so Composer will pick it up
        putenv('COMPOSER_HOME=' . $homeDir);

        $this->io
            ->expects($this->at(0))
            ->method('setAuthentication')
            ->with('satis.loc', 'my_satis_user', 'my_satis_pass');

        $this->io
            ->expects($this->at(1))
            ->method('setAuthentication')
            ->with('http://satis.loc/packages.json', 'my_satis_user', 'my_satis_pass');

        $this->io
            ->expects($this->at(2))
            ->method('setAuthentication')
            ->with('https://satis.loc/packages.json', 'my_satis_user', 'my_satis_pass');

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
    }

    /**
     * Helper method for creating a sample auth.json file in the given $dir.
     *
     * @param string $dir
     * @param array $credentials
     */
    protected function createAuthFileWithCredentials($dir, array $credentials)
    {
        // build sample auth file and store it in the given $dir
        $authFileContent = array(
            'config' => array(
                'basic-auth' => $credentials
            )
        );
        file_put_contents($dir . '/auth.json', json_encode($authFileContent));
    }

    /**
     * @test
     */
    public function localConfigAlone()
    {
        $user = 'my_satis_user';
        $password = 'my_satis_pass';
        $host = 'satis.loc';
        $credentials = array(
            $host => array(
                'username' => $user,
                'password' => $password
            )
        );
        $this->createAuthFileWithCredentials(getcwd(), $credentials);

        $this->io
            ->expects($this->at(0))
            ->method('setAuthentication')
            ->with( $host, $user, $password );

        $this->io
            ->expects($this->at(1))
            ->method('setAuthentication')
            ->with("http://$host/packages.json", $user, $password );

        $this->io
            ->expects($this->at(2))
            ->method('setAuthentication')
            ->with("https://$host/packages.json", $user, $password );

        $this->authPlugin->activate($this->composer, $this->io);
    }

    /**
     * Checks if config from auth.json and local config are correctly merged together.
     *
     * @test
     */
    public function localConfigAuthFile()
    {
        // Local credentials
        $localUser = 'foo';
        $localPassword = 'bar';
        $localHost = 'another_satis.loc';
        $localCredentials = array(
            $localHost => array(
                'username' => $localUser,
                'password' => $localPassword
            )
        );
        $this->createAuthFileWithCredentials(getcwd(), $localCredentials);

        // Credentials from global auth.json
        $globalUser = 'my_satis_user';
        $globalPassword = 'my_satis_pass';
        $globalHost = 'satis.loc';
        $globalCredentials = array(
            $globalHost => array(
                'username' => $globalUser,
                'password' => $globalPassword
            )
        );
        $homeDir = vfsStream::url(self::HOME);
        $this->createAuthFileWithCredentials($homeDir, $globalCredentials);
        // expose the given $dir as environment variable so Composer will pick it up
        putenv('COMPOSER_HOME=' . $homeDir);

        $this->io
            ->expects($this->at(0))
            ->method('setAuthentication')
            ->with( $localHost, $localUser, $localPassword );

        $this->io
            ->expects($this->at(1))
            ->method('setAuthentication')
            ->with("http://$localHost/packages.json", $localUser, $localPassword );

        $this->io
            ->expects($this->at(2))
            ->method('setAuthentication')
            ->with("https://$localHost/packages.json", $localUser, $localPassword );

        $this->io
            ->expects($this->at(3))
            ->method('setAuthentication')
            ->with( $globalHost, $globalUser, $globalPassword );

        $this->io
            ->expects($this->at(4))
            ->method('setAuthentication')
            ->with("http://$globalHost/packages.json", $globalUser, $globalPassword );

        $this->io
            ->expects($this->at(5))
            ->method('setAuthentication')
            ->with("https://$globalHost/packages.json", $globalUser, $globalPassword );

        $this->authPlugin->activate($this->composer, $this->io);
    }

    /**
     * Checks if configuration from auth.json is correctly overriden by local config.
     *
     * @test
     */
    public function localConfigOverrideAuthFile()
    {
        // Local credentials
        $localUser = 'another_satis_user';
        $localPassword = 'another_satis_password';
        $localHost = 'satis.loc';
        $localHost2 = 'something.com';
        $localUser2 = 'foo';
        $localPassword2 = 'bar';
        $localCredentials = array(
            $localHost => array(
                'username' => $localUser,
                'password' => $localPassword
            ),
            $localHost2 => array(
                'username' => $localUser2,
                'password' => $localPassword2
            )
        );
        $this->createAuthFileWithCredentials(getcwd(), $localCredentials);

        // Credentials from global auth.json
        $globalUser = 'my_satis_user';
        $globalPassword = 'my_satis_pass';
        $globalHost = 'satis.loc';
        $globalCredentials = array(
            $globalHost => array(
                'username' => $globalUser,
                'password' => $globalPassword
            )
        );
        $homeDir = vfsStream::url(self::HOME);
        $this->createAuthFileWithCredentials($homeDir, $globalCredentials);
        // expose the given $dir as environment variable so Composer will pick it up
        putenv('COMPOSER_HOME=' . $homeDir);

        $this->io
            ->expects($this->at(0))
            ->method('setAuthentication')
            ->with( $localHost, $localUser, $localPassword );

        $this->io
            ->expects($this->at(1))
            ->method('setAuthentication')
            ->with("http://$localHost/packages.json", $localUser, $localPassword );

        $this->io
            ->expects($this->at(2))
            ->method('setAuthentication')
            ->with("https://$localHost/packages.json", $localUser, $localPassword );

        $this->io
            ->expects($this->at(3))
            ->method('setAuthentication')
            ->with( $localHost2, $localUser2, $localPassword2 );

        $this->io
            ->expects($this->at(4))
            ->method('setAuthentication')
            ->with("http://$localHost2/packages.json", $localUser2, $localPassword2 );

        $this->io
            ->expects($this->at(5))
            ->method('setAuthentication')
            ->with("https://$localHost2/packages.json", $localUser2, $localPassword2 );

        $this->authPlugin->activate($this->composer, $this->io);
    }
}