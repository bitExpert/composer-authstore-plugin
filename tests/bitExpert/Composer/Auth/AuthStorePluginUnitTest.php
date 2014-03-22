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
    /**
     * @var \org\bovigo\vfs\vfsStreamDirectory
     */
    private $homeDir;
    /**
     * @var \org\bovigo\vfs\vfsStreamDirectory
     */
    private $projectDir;
    /**
     * @var \Composer\IO\IOInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $io;
    /**
     * @var \Composer\Composer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $composer;
    /**
     * @var AuthStorePlugin|\PHPUnit_Framework_MockObject_MockObject
     */
    private $authPlugin;
    /**
     * @var int
     */
    private $idx;

    /**
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        parent::setUp();

        // resetting idx counter for every test run
        $this->idx = 0;

        // setup COMPOSER_HOME and project folder in vfs
        $root = vfsStream::setup('/tests', null, array('home' => array(), 'project' => array()));
        $this->homeDir = $root->getChild('home')->url();
        $this->projectDir = $root->getChild('project')->url();
        putenv('COMPOSER_HOME=' . $this->homeDir);

        $this->io = $this->getMock('\Composer\IO\IOInterface');
        $this->composer = $this->getMock('\Composer\Composer');

        // mock the AuthStoragePlugin to be able to return the path the vfs folder instead of relying on the getcwd()
        // call
        $this->authPlugin = $this->getMock('\bitExpert\Composer\Auth\AuthStorePlugin', array('getProjectDir'));
        $this->authPlugin->expects($this->any())
            ->method('getProjectDir')
            ->will($this->returnValue($this->projectDir));
    }

    /**
     * @test
     */
    public function emptyGlobalAuthFileWillBeIgnored()
    {
        $this->createAuthFile($this->homeDir);

        $this->io->expects($this->never())
            ->method('setAuthentication');

        $this->authPlugin->activate($this->composer, $this->io);
    }

    /**
     * @test
     */
    public function emptyLocalAuthFileWillBeIgnored()
    {
        $this->createAuthFile($this->projectDir);

        $this->io->expects($this->never())
            ->method('setAuthentication');

        $this->authPlugin->activate($this->composer, $this->io);
    }

    /**
     * @test
     * @expectedException \Seld\JsonLint\ParsingException
     */
    public function invalidJsonWillThrowException()
    {
        file_put_contents($this->homeDir . '/auth.json', '"config": { "basic-auth": {}, }');

        $this->io->expects($this->never())
            ->method('setAuthentication');

        $this->authPlugin->activate($this->composer, $this->io);
    }

    /**
     * @test
     */
    public function emptyLocalAuthFileAndEmptyGlobalAuthFileWillBeIgnored()
    {
        $this->createAuthFile($this->homeDir);
        $this->createAuthFile($this->projectDir);

        $this->io->expects($this->never())
            ->method('setAuthentication');

        $this->authPlugin->activate($this->composer, $this->io);
    }

    /**
     * @test
     */
    public function readingGlobalAuthFileAndPassingTheCredentialsToIOInterface()
    {
        $credentials = array(
            'satis.loc' => array(
                'username' => 'my_satis_user',
                'password' => 'my_satis_pass'
            )
        );
        $this->createAuthFile($this->homeDir, $credentials);
        $this->configureExpectationsFor($credentials);

        $this->authPlugin->activate($this->composer, $this->io);
    }

    /**
     * @test
     */
    public function readingLocalAuthFileAndPassingTheCredentialsToIOInterface()
    {
        $credentials = array(
            'project.satis.loc' => array(
                'username' => 'my_satis_user_pr',
                'password' => 'my_satis_pass_pr'
            )
        );
        $this->createAuthFile($this->projectDir, $credentials);
        $this->configureExpectationsFor($credentials);

        $this->authPlugin->activate($this->composer, $this->io);
    }

    /**
     * @test
     */
    public function localAuthFileGetsMergedWithGlobalAuthFile()
    {
        // Local credentials
        $localCredentials = array(
            'another_satis.loc' => array(
                'username' => 'foo',
                'password' => 'bar'
            )
        );
        $this->createAuthFile($this->projectDir, $localCredentials);
        $this->configureExpectationsFor($localCredentials);

        // Global Credentials
        $globalCredentials = array(
            'satis.loc' => array(
                'username' => 'my_satis_user',
                'password' => 'my_satis_pass'
            )
        );
        $this->createAuthFile($this->homeDir, $globalCredentials);
        $this->configureExpectationsFor($globalCredentials);

        $this->authPlugin->activate($this->composer, $this->io);
    }

    /**
     * @test
     */
    public function localAuthFileOverridesGlobalAuthFile()
    {
        // Local credentials
        $localCredentials = array(
            'satis.loc' => array(
                'username' => 'local_satis_user',
                'password' => 'local_satis_password'
            ),
            'satis.foo' => array(
                'username' => 'foo',
                'password' => 'bar'
            )
        );
        $this->createAuthFile($this->projectDir, $localCredentials);

        // Global credentials
        $globalCredentials = array(
            'satis.loc' => array(
                'username' => 'my_satis_user',
                'password' => 'my_satis_pass'
            )
        );
        $this->createAuthFile($this->homeDir, $globalCredentials);

        // merge local + global credentials to set-up the expectations
        $mergedCredentials = $localCredentials + $globalCredentials;
        $this->configureExpectationsFor($mergedCredentials);

        $this->authPlugin->activate($this->composer, $this->io);
    }

    /**
     * Helper method for creating a auth.json file with the given $credentials in the given $dir.
     *
     * @param string $dir
     * @param array $credentials
     */
    protected function createAuthFile($dir, array $credentials = array())
    {
        $authFileContent = array(
            'config' => array()
        );

        if(count($credentials) > 0) {
            $authFileContent['config']['basic-auth'] = $credentials;
        }

        file_put_contents($dir . '/auth.json', json_encode($authFileContent));
    }

    /**
     * Helper method for setting up the the expectations for the given $credentials.
     *
     * @param array $credentials
     */
    protected function configureExpectationsFor($credentials = array())
    {
        foreach($credentials as $host => $config) {
            $this->io
                ->expects($this->at($this->idx++))
                ->method('setAuthentication')
                ->with($host, $config['username'], $config['password']);

            $this->io
                ->expects($this->at($this->idx++))
                ->method('setAuthentication')
                ->with('http://'.$host.'/packages.json', $config['username'], $config['password']);

            $this->io
                ->expects($this->at($this->idx++))
                ->method('setAuthentication')
                ->with('https://'.$host.'/packages.json', $config['username'], $config['password']);
        }
    }
}