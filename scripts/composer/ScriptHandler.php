<?php

namespace ThunderDevelop\composer;

use Composer\Composer;
use Composer\Package\PackageInterface;
use Composer\Repository\VcsRepository;
use Composer\Script\Event;
use DrupalFinder\DrupalFinder;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class ScriptHandler.
 */
class ScriptHandler {

  /**
   * Download dev packages before composer update.
   *
   * @param \Composer\Script\Event $event
   *   The composer event.
   */
  public static function downloadDevelopPackages(Event $event) {
    $fs = new Filesystem();

    $io = $event->getIO();
    $composer = $event->getComposer();
    $repositoryManager = $composer->getRepositoryManager();
    $rootPackage = $composer->getPackage();

    $rootExtra = $rootPackage->getExtra();
    $localPackages = $rootExtra['local-develop-packages'];

    foreach ($localPackages as $packageString => $packageVersion) {
      $packages = $repositoryManager->findPackages($packageString, $packageVersion . '-dev');

      foreach ($packages as $package) {
        $installPath = self::getInstallPath($package, $composer);

        if (!$fs->exists($installPath)) {
          $repository = $package->getRepository();
          if ($repository instanceof VcsRepository) {
            $repositoryUrl = $repository->getDriver()->getUrl();
            exec(sprintf("git clone -b %s %s %s", $packageVersion, $repositoryUrl, $installPath));
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
      $gitCommand = 'git -C ' . $composerRoot . '/' . $info['install_path'];

      $localBranch = trim(shell_exec($gitCommand . ' rev-parse --abbrev-ref HEAD'));

      exec($gitCommand . ' fetch --quiet');
      $gitStatus = shell_exec($gitCommand . ' status --porcelain');
      if (!empty($gitStatus)) {
        $io->write('Stash local changes in ' . $info['package'] . ':' . $localBranch, TRUE);
        exec($gitCommand . ' stash --include-untracked');
      }

      if ($localBranch !== $info['branch']) {
        $io->write('Checkout ' . $info['package'] . ':' . $info['branch'], TRUE);
        exec($gitCommand . ' checkout --quiet ' . $info['branch']);
      }

      $io->write('Merge remote changes into ' . $info['package'] . ':' . $info['branch'], TRUE);
      exec($gitCommand . ' merge --quiet');
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
    $rootExtra = $composer->getPackage()->getExtra();
    $packages = $rootExtra['local-develop-packages'];

    foreach ($packages as $packageString => $packageVersion) {
      $packages = $composer->getRepositoryManager()
        ->findPackages($packageString, $packageVersion . '-dev');

      foreach ($packages as $package) {
        $repository = $package->getRepository();
        if ($repository instanceof VcsRepository) {
          $info = [];
          $info['package'] = $packageString;
          $info['install_path'] = self::getInstallPath($package, $composer);
          $info['url'] = $repository->getDriver()->getUrl();
          $info['branch'] = $packageVersion;
          $repositoriesInfo[] = $info;
        }
      }
    }
    return $repositoriesInfo;
  }

  /**
   * Return the install path based on package type.
   *
   * @param \Composer\Package\PackageInterface $package
   *   The package to get the path for.
   * @param \Composer\Composer $composer
   *   The composer obbject.
   *
   * @return bool|string
   *   The installer path or FALSE if not found.
   */
  protected static function getInstallPath(PackageInterface $package, Composer $composer) {
    $type = $package->getType();

    $prettyName = $package->getPrettyName();
    if (strpos($prettyName, '/') !== FALSE) {
      [$vendor, $name] = explode('/', $prettyName);
    }
    else {
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
        if ($customPath !== FALSE) {
          return self::templatePath($customPath, $availableVars);
        }
      }
    }

    return FALSE;
  }

  /**
   * Search through a passed paths array for a custom install path.
   *
   * @param array[] $paths
   *   An array of paths.
   * @param string $name
   *   The package to search for.
   * @param string $type
   *   The type of the package.
   * @param string|null $vendor
   *   The vendor type.
   *
   * @return string
   *   The custom installer path.
   */
  protected static function mapCustomInstallPaths(array $paths, $name, $type, $vendor = NULL) {
    foreach ($paths as $path => $names) {
      if (in_array($name, $names) || in_array('type:' . $type, $names) || in_array('vendor:' . $vendor, $names)) {
        return $path;
      }
    }

    return FALSE;
  }

  /**
   * Replace vars in a path.
   *
   * @param string $path
   *   The path.
   * @param array $vars
   *   The vars.
   *
   * @return string
   */
  protected static function templatePath($path, array $vars = []) {
    if (strpos($path, '{') !== FALSE) {
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
