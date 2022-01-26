<?php

declare(strict_types=1);

use Rector\Core\Configuration\Option;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\ClassMethod\AddArrayReturnDocTypeRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
  // get parameters
  $parameters = $containerConfigurator->parameters();

  $parameters->set(Option::SKIP, [
    // rules
    AddArrayReturnDocTypeRector::class,
    TypedPropertyFromStrictConstructorRector::class,

    // files
    '*/node_modules/*',
  ]);

  $drupalRoot = __DIR__ . '/docroot';

  $parameters->set(Option::BOOTSTRAP_FILES, [
    $drupalRoot . '/core/tests/bootstrap.php',
  ]);

  $parameters->set(Option::AUTO_IMPORT_NAMES, TRUE);
  $parameters->set(Option::IMPORT_SHORT_CLASSES, FALSE);
  $parameters->set(Option::IMPORT_DOC_BLOCKS, FALSE);

  $parameters->set(Option::FILE_EXTENSIONS, [
    'php',
    'module',
    'theme',
    'install',
    'profile',
    'inc',
    'engine',
  ]);

  // Define what rule sets will be applied
  $containerConfigurator->import(LevelSetList::UP_TO_PHP_74);
  $containerConfigurator->import(SetList::TYPE_DECLARATION_STRICT);
};
