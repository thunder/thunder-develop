<?php
/**
 * @file
 * Contains \DrupalProject\composer\ScriptHandler.
 */
namespace DrupalProject\composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\Package\Package;
use Composer\Repository\VcsRepository;
use Composer\Script\Event;
use Composer\Repository\Vcs\GitHubDriver;
use DrupalFinder\DrupalFinder;
use function GuzzleHttp\Psr7\str;
use Robo\Task\Archive\Pack;
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
      if (!$fs->exists($drupalRoot . '/'. $dir)) {
        $fs->mkdir($drupalRoot . '/'. $dir);
        $fs->touch($drupalRoot . '/'. $dir . '/.gitkeep');
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
      $event->getIO()->write("Create a sites/default/settings.php file with chmod 0666");
    }
    // Create the files directory with chmod 0777
    if (!$fs->exists($drupalRoot . '/sites/default/files')) {
      $oldmask = umask(0);
      $fs->mkdir($drupalRoot . '/sites/default/files', 0777);
      umask($oldmask);
      $event->getIO()->write("Create a sites/default/files directory with chmod 0777");
    }
  }

  public static function downLoadDistribution(Event $event) {
    $composer = $event->getComposer();
    $extra = $composer->getPackage()->getExtra();

    $package = $extra['thunder-package'];
    $packageName = explode('/', $package)[1];

    foreach($extra['installer-paths'] as $installerPath => $types) {
      if(in_array('type:drupal-profile', $types)) {
        $thunderRoot = str_replace('{$name}', $packageName, $installerPath);
        self::cloneDistribution($event, $thunderRoot);
        break;
      }
    }
  }

  /**
   * @param \Composer\Script\Event $event
   * @param string $thunderRoot
   */
  public static function cloneDistribution(Event $event, $thunderRoot): void {
    $composer = $event->getComposer();
    $extra = $composer->getPackage()->getExtra();
    $package = $extra['thunder-package'];

    $repositories = $composer->getRepositoryManager()->getRepositories();
    $fs = new Filesystem();
    // Download distribution, if necessary.
    if (!$fs->exists($thunderRoot)) {
      foreach ($repositories as $repository) {
        if ($repository instanceof VcsRepository) {
          /** @var \Composer\Repository\Vcs\GitHubDriver $gitDriver */
          $gitDriver = $repository->getDriver();
          $composerInformation = $gitDriver->getComposerInformation('develop');
          if ($composerInformation['name'] === $package) {
            // TODO: use $composer top actually download the repository.

            $repositoryUrl = $gitDriver->getUrl();
            exec('git clone ' . $repositoryUrl . ' ' . $thunderRoot);
            $event->getIO()->write("Downloaded Thunder");
            break;
          }
        }
      }
    }
  }
}
