<?php
/**
 * @file
 * Contains \DrupalProject\composer\ScriptHandler.
 */
namespace ThunderDevelop\composer;

use Composer\Composer;
use Composer\Package\PackageInterface;
use Composer\Script\Event;

use DrupalFinder\DrupalFinder;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

class ScriptHandler {

  public static function createRequiredFiles(Event $event) {
    $fs = new Filesystem();
    $drupalFinder = new DrupalFinder();
    $drupalFinder->locateRoot(getcwd());
    $drupalRoot = $drupalFinder->getDrupalRoot();
    $dirs = [
      'modules',
      'profiles',
      'themes',
    ];
    // Required for unit testing
    foreach ($dirs as $dir) {
      if (!$fs->exists($drupalRoot . '/' . $dir)) {
        $fs->mkdir($drupalRoot . '/' . $dir);
        $fs->touch($drupalRoot . '/' . $dir . '/.gitkeep');
      }
    }
    // Prepare the settings file for installation
    if (!$fs->exists($drupalRoot . '/sites/default/settings.php') and $fs->exists($drupalRoot . '/sites/default/default.settings.php')) {
      $fs->copy($drupalRoot . '/sites/default/default.settings.php', $drupalRoot . '/sites/default/settings.php');
      require_once $drupalRoot . '/core/includes/bootstrap.inc';
      require_once $drupalRoot . '/core/includes/install.inc';
      $settings['config_directories'] = [
        CONFIG_SYNC_DIRECTORY => (object) [
          'value' => Path::makeRelative($drupalFinder->getComposerRoot() . '/config/sync', $drupalRoot),
          'required' => TRUE,
        ],
      ];
      drupal_rewrite_settings($settings, $drupalRoot . '/sites/default/settings.php');
      $fs->chmod($drupalRoot . '/sites/default/settings.php', 0666);
      $event->getIO()
        ->write("Create a sites/default/settings.php file with chmod 0666");
    }
    // Create the files directory with chmod 0777
    if (!$fs->exists($drupalRoot . '/sites/default/files')) {
      $oldmask = umask(0);
      $fs->mkdir($drupalRoot . '/sites/default/files', 0777);
      umask($oldmask);
      $event->getIO()
        ->write("Create a sites/default/files directory with chmod 0777");
    }
  }

  public static function downloadDevelopPackages(Event $event) {
    $fs = new Filesystem();

    $io = $event->getIO();
    $composer = $event->getComposer();
    $repositoryManager = $composer->getRepositoryManager();
    $rootPackage = $composer->getPackage();

    $rootExtra = $rootPackage->getExtra();
    $packages = $rootExtra['local-develop-packages'];

    foreach ($packages as $packageString => $packageVersion) {
      $package = $repositoryManager->findPackage($packageString, $packageVersion);
      if ($package) {
        $installPath = self::getInstallPath($package, $composer);

        if (!$fs->exists($installPath)) {
          $repository = $package->getRepository();
          if ($gitDriver = $repository->getDriver()) {
            $gitDriver = $repository->getDriver();
            $repositoryUrl = $gitDriver->getUrl();
            $branchOption = (0 === strpos($packageVersion, 'dev-')) ? '-b ' . substr($packageVersion, strlen('dev-')) . ' ' : '';
            exec('git clone  ' . $branchOption . $repositoryUrl . ' ' . $installPath);
            $io->write('Cloning repository: ' . $packageString);
          }
        }
      }
    }
  }

  /**
   * Reset local repositories to the default branch.
   *
   * @param \Composer\Script\Event $event
   *   The script event.
   */
  public static function resetLocalRepositories(Event $event) {
    $io = $event->getIO();
    $repositoriesInfo = self::getLocalRepositoriesInfo($event);
    $drupalFinder = new DrupalFinder();
    $drupalFinder->locateRoot(getcwd());
    $composerRoot = $drupalFinder->getComposerRoot();

    $io->write(PHP_EOL);
    foreach ($repositoriesInfo as $key => $info) {
      $localBranch = trim(shell_exec('git -C ' . $composerRoot . '/' . $info['install_path'] . ' rev-parse --abbrev-ref HEAD'));

      if ($localBranch === $info['branch']) {
        $io->write($info['package'] . ' is already on branch ' . $info['branch'], TRUE);
        continue;
      }

      $gitStatus = shell_exec('git -C ' . $composerRoot . '/' . $info['install_path'] . ' status --porcelain --untracked-files=no');
      if (!empty($gitStatus)) {
        $io->write('Stash local changes in ' . $info['package'] . ':' . $localBranch, TRUE);
        exec('git -C ' . $composerRoot . '/' . $info['install_path'] . ' stash');
      }

      $io->write('Checkout ' . $info['package'] . ':' . $info['branch'], TRUE);
      exec('git -C ' . $composerRoot . '/' . $info['install_path'] . ' checkout -q ' . $info['branch']);
      exec('git -C ' . $composerRoot . '/' . $info['install_path'] . ' pull -q');
    }
  }

  /**
   * Collect information about local repositories.
   *
   * Retrieve available informazion about the repositories defined in the
   * local-develop-packages key of the composer.json.
   *
   * @param \Composer\Script\Event $event
   *   The script event.
   *
   * @return array
   *   The collected repositories.
   */
  protected static function getLocalRepositoriesInfo(Event $event) {
    $repositoriesInfo = [];
    $composer = $event->getComposer();
    $repositoryManager = $composer->getRepositoryManager();
    $rootPackage = $composer->getPackage();

    $rootExtra = $rootPackage->getExtra();
    $packages = $rootExtra['local-develop-packages'];

    foreach ($packages as $packageString => $packageVersion) {
      $package = $repositoryManager->findPackage($packageString, $packageVersion);
      if (!$package) {
        continue;
      }

      $repository = $package->getRepository();
      if ($gitDriver = $repository->getDriver()) {
        $info = [];
        $info['package'] = $packageString;
        $info['install_path'] = self::getInstallPath($package, $composer);
        $info['url'] = $gitDriver->getUrl();
        $info['branch'] = (0 === strpos($packageVersion, 'dev-')) ? substr($packageVersion, strlen('dev-')) : '';
        $repositoriesInfo[] = $info;
      }
    }
    return $repositoriesInfo;
  }

  /**
   * Return the install path based on package type.
   *
   * @param \Composer\Package\PackageInterface $package
   * @param \Composer\Composer $composer
   *
   * @return bool|string
   */
  protected static function getInstallPath(PackageInterface $package, Composer $composer) {
    $type = $package->getType();

    $prettyName = $package->getPrettyName();
    if (strpos($prettyName, '/') !== false) {
      list($vendor, $name) = explode('/', $prettyName);
    } else {
      $vendor = '';
      $name = $prettyName;
    }

    $availableVars = compact('name', 'vendor', 'type');

    $extra = $package->getExtra();
    if (!empty($extra['installer-name'])) {
      $availableVars['name'] = $extra['installer-name'];
    }

    if ($composer->getPackage()) {
      $extra = $composer->getPackage()->getExtra();
      if (!empty($extra['installer-paths'])) {
        $customPath = self::mapCustomInstallPaths($extra['installer-paths'], $prettyName, $type, $vendor);
        if ($customPath !== false) {
          return self::templatePath($customPath, $availableVars);
        }
      }
    }

    return false;
  }


  /**
   * Search through a passed paths array for a custom install path.
   *
   * @param  array  $paths
   * @param  string $name
   * @param  string $type
   * @param  string $vendor = NULL
   * @return string
   */
  protected static function mapCustomInstallPaths(array $paths, $name, $type, $vendor = NULL) {
    foreach ($paths as $path => $names) {
      if (in_array($name, $names) || in_array('type:' . $type, $names) || in_array('vendor:' . $vendor, $names)) {
        return $path;
      }
    }

    return false;
  }

  /**
   * Replace vars in a path.
   *
   * @param  string $path
   * @param  array  $vars
   * @return string
   */
  protected static function templatePath($path, array $vars = array()) {
    if (strpos($path, '{') !== false) {
      extract($vars);
      preg_match_all('@\{\$([A-Za-z0-9_]*)\}@i', $path, $matches);
      if (!empty($matches[1])) {
        foreach ($matches[1] as $var) {
          $path = str_replace('{$' . $var . '}', $$var, $path);
        }
      }
    }

    return $path;
  }

}
