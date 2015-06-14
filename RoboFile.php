<?php
/**
 * This is project's console commands configuration for Robo task runner.
 * Prepared to be used with Silex
 *
 * @see http://robo.li/
 */

use Robo\Config;
class RoboFile extends \Robo\Tasks
{
    protected $host = 'domain.com';
    protected $username = 'jacek';
    protected $scm = 'git@bitbucket.org:opcode_pl/test.git';
    protected $baseDir = '/var/www/domain.com/';
    protected $releaseDate;
    protected $releaseDir;

    /**
     * TODO:
     * revert
     * phinx
     * phinx revert
     * reload supervisord
     * change file owners
     */
    public function deploy()
    {
        $this->setReleaseDate();

        $this->taskSshExec($this->host, $this->username)
            ->remoteDir($this->baseDir)

            ->exec('echo ➜ Create shared directories')
            ->exec('mkdir -p shared/cache')
            ->exec('mkdir -p shared/logs')
            ->exec('chmod 777 shared/cache')
            ->exec('chmod 777 shared/logs')

            ->exec('echo ➜ Create release directory')
            ->exec('mkdir -p '.$this->releaseDir())

            ->exec('echo ➜ Clone repository')
            ->exec('git clone -b master '.$this->scm.' '.$this->releaseDir())
            ->exec('rm -rf '.$this->releaseDir().'/.git')
            ->exec('rm -rf '.$this->releaseDir().'/var/cache')
            ->exec('rm -rf '.$this->releaseDir().'/var/logs')
            ->exec('mkdir -p '.$this->releaseDir().'/var')

            ->exec('echo ➜ Create links for shared directories')
            ->exec('ln -s '. $this->baseDir .'shared/cache '.$this->releaseDir().'/var/cache')
            ->exec('ln -s '. $this->baseDir .'shared/logs '.$this->releaseDir().'/var/logs')

            ->exec('echo ➜ Composer install')
            ->exec('composer install --prefer-dist --no-scripts --working-dir '.$this->releaseDir())

            ->exec('echo ➜ Composer dumpautoload')
            ->exec('composer dumpautoload --optimize -d '.$this->releaseDir())

            ->exec('echo ➜ Create live link')
            ->exec('ln -nfs ' . $this->baseDir.$this->releaseDir().' '. $this->baseDir .'current')

            ->exec('echo ➜ Reload php5-fpm')
            ->exec('sudo service php5-fpm reload')

            ->exec('echo ➜ Remove old releases')
            ->exec('rm -rf `ls -dt '. $this->baseDir.'releases/* | tail -n +6`')
            ->run();
    }

    protected function setReleaseDate()
    {
        $this->releaseDate = date('YmhHis');
    }


    protected function releaseDir()
    {
        return 'releases/'.$this->releaseDate;
    }

    protected function getReleaseDate()
    {
        return $this->releaseDate;
    }
}