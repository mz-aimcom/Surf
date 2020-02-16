<?php

namespace TYPO3\Surf\Task\Neos\Flow;

/*
 * This file is part of TYPO3 Surf.
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

use InvalidArgumentException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TYPO3\Surf\Application\Neos\Flow as FlowApplication;
use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Domain\Model\Task;
use TYPO3\Surf\Domain\Service\ShellCommandServiceAwareInterface;
use TYPO3\Surf\Domain\Service\ShellCommandServiceAwareTrait;
use Webmozart\Assert\Assert;

/**
 * This tasks clears the list of Flow Framework cache
 *
 * It takes the following options:
 *
 * * flushCacheList (required) - An array with extension keys to install.
 *
 * Example:
 *  $workflow
 *      ->setTaskOptions(\TYPO3\Surf\Task\TYPO3\CMS\FlushCacheListTask::class, [
 *              'flushCacheList' => [
 *                  'Neos_Fusion_Content',
 *                  'Flow_Session_MetaData',
 *                  'Flow_Session_Storage',
 *              ],
 *              'phpBinaryPathAndFilename', '/path/to/php',
 *          ]
 *      );
 */
class FlushCacheListTask extends Task implements ShellCommandServiceAwareInterface
{
    use ShellCommandServiceAwareTrait;

    /**
     * Execute this task
     *
     * @param Node $node
     * @param Application|FlowApplication $application
     * @param Deployment $deployment
     * @param array $options
     *
     * @throws InvalidArgumentException
     */
    public function execute(Node $node, Application $application, Deployment $deployment, array $options = [])
    {
        Assert::isInstanceOf($application, FlowApplication::class, sprintf('Flow application needed for MigrateTask, got "%s"', get_class($application)));
        Assert::greaterThanEq($application->getVersion(), '2.3', sprintf('FlushCacheListTask is available since Flow Framework 2.3, your application version is "%s"', $application->getVersion()));

        $options = $this->configureOptions($options);

        $targetPath = $deployment->getApplicationReleasePath($application);

        foreach ($options['flushCacheList'] as $cache) {
            $deployment->getLogger()->debug(sprintf('Flush cache with identifier "%s"', $cache));
            $this->shell->executeOrSimulate(
                $application->buildCommand($targetPath, 'cache:flushone', ['--identifier', $cache], $options['phpBinaryPathAndFilename']),
                $node,
                $deployment
            );
        }
    }

    /**
     * Simulate this task
     *
     * @param Node $node
     * @param Application $application
     * @param Deployment $deployment
     * @param array $options
     */
    public function simulate(Node $node, Application $application, Deployment $deployment, array $options = [])
    {
        $this->execute($node, $application, $deployment, $options);
    }

    /**
     * @param OptionsResolver $resolver
     */
    protected function resolveOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('flushCacheList');
        $resolver->setAllowedValues('flushCacheList', static function ($value) {
            return trim($value) !== '';
        });

        $resolver->setNormalizer('flushCacheList', static function (Options $options, $value) {
            return is_array($value) ? $value : explode(',', $value);
        });

        $resolver->setDefault('phpBinaryPathAndFilename', 'php')
            ->setAllowedTypes('phpBinaryPathAndFilename', 'string');
    }
}
