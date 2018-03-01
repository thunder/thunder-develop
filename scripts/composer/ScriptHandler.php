<?php
/**
 * @file
 * Contains \DrupalProject\composer\ScriptHandler.
 */
namespace ThunderDevelop\composer;

use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\Installer;
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
    $installationManager = $composer->getInstallationManager();
    $rootPackage = $composer->getPackage();

    $rootExtra = $rootPackage->getExtra();
    $packages = $rootExtra['local-develop-packages'];

    $missingFiles = self::findMissingMergeIncludes($rootExtra['merge-plugin']);

    foreach ($packages as $packageString => $packageVersion) {
      $package = $repositoryManager->findPackage($packageString, $packageVersion);
      if ($package) {
        $installPath = $installationManager->getInstaller($package->getType())
          ->getInstallPath($package);
        if (!$fs->exists($installPath)) {
          $repository = $package->getRepository();
          if ($gitDriver = $repository->getDriver()) {
            $gitDriver = $repository->getDriver();
            $repositoryUrl = $gitDriver->getUrl();
            exec('git clone ' . $repositoryUrl . ' ' . $installPath);
          }
        }
      }
    }

    $missingFilesAfterDownloads = self::findMissingMergeIncludes($rootExtra['merge-plugin']);

    // Install new requirements, if a file that will be merged has been added.
    if (!empty(array_diff($missingFiles, $missingFilesAfterDownloads))) {
      $config = $composer->getConfig();

      $installer = Installer::create(
        $io,
        $composer
      );

      $installer->setPreferSource($config->get('preferred-install') === 'source');
      $installer->setPreferDist($config->get('preferred-install') === 'dist');
      $installer->setDevMode($event->isDevMode());
      $installer->run();

    }
  }

  /**
   * Find missing files, that should be merged.
   *
   * @param $mergePluginConfig
   *  The merge plugin configuration from the extra section.
   * @return array
   *  Files that are missing.
   */
  protected static function findMissingMergeIncludes($mergePluginConfig) {
    $fs = new Filesystem();
    $missingFiles = [];

    foreach ($mergePluginConfig['include'] as $mergeInclude) {
      if (!$fs->exists($mergeInclude)) {
        $missingFiles[] = $mergeInclude;
      }
    }

    return $missingFiles;
  }
}
