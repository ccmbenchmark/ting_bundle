<?php

use \atoum\atoum;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;

$report = $script->addDefaultReport();
$coverageField = new atoum\report\fields\runner\coverage\html('Ting', __DIR__ . '/tests/coverage/');
$script->noCodeCoverageForClasses('Symfony\Component\Validator\Constraint', 'Symfony\Component\Validator\ConstraintValidator', 'Symfony\Component\DependencyInjection\Extension\Extension', 'Symfony\Component\HttpKernel\DependencyInjection\Extension');
$coverageField->setRootUrl('file://' . __DIR__ . '/tests/coverage/index.html');
$report->addField($coverageField);
/**
 * @var $runner \atoum\atoum\scripts\runner
 */
$testsDirectory = __DIR__ . '/tests/units/TingBundle';
$subDirectories = glob($testsDirectory . '/*', GLOB_ONLYDIR);
$files = glob($testsDirectory . '/*.php');

if (!interface_exists(ValueResolverInterface::class) || !class_exists(ValueResolver::class)) {
    // Exclude ArgumentResolver from tests, available only in SF6+
    $subDirectories = array_filter($subDirectories, fn($directory) => $directory !== $testsDirectory . '/ArgumentResolver' );
}
foreach ($subDirectories as $directory) {
    $runner->addTestsFromPattern($directory . '/*');
}
foreach ($files as $file) {
    $runner->addTestsFromPattern($files);
}
