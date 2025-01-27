<?php

namespace TYPO3\Surf\Domain\Model;

/*
 * This file is part of TYPO3 Surf.
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

use Exception;
use TYPO3\Surf\Domain\Enum\DeploymentStatus;
use TYPO3\Surf\Exception\DeploymentLockedException;
use TYPO3\Surf\Exception\InvalidConfigurationException;

/**
 * A simple workflow
 */
class SimpleWorkflow extends Workflow
{
    /**
     * If FALSE no rollback will be done on errors
     */
    protected bool $enableRollback = true;

    /**
     * Order of stages that will be executed
     */
    protected array $stages = [
        // Initialize directories etc. (first time deploy)
        'initialize',
        // Lock deployment
        'lock',
        // Local preparation of and packaging of application assets (code and files)
        'package',
        // Transfer of application assets to the node
        'transfer',
        // Update the application assets on the node
        'update',
        // Migrate (Doctrine, custom)
        'migrate',
        // Prepare final release (e.g. warmup)
        'finalize',
        // Smoke test
        'test',
        // Do symlink to current release
        'switch',
        // Delete temporary files or previous releases
        'cleanup',
        // Unlock deployment
        'unlock',
    ];

    /**
     * Sequentially execute the stages for each node, so first all nodes will go through the initialize stage and
     * then the next stage will be executed until the final stage is reached and the workflow is finished.
     *
     * A rollback will be done for all nodes as long as the stage switch was not completed.
     */
    public function run(Deployment $deployment): void
    {
        parent::run($deployment);

        $applications = $deployment->getApplications();
        if (count($applications) === 0) {
            throw InvalidConfigurationException::createNoApplicationConfigured();
        }

        $nodes = $deployment->getNodes();
        if (count($nodes) === 0) {
            throw InvalidConfigurationException::createNoNodesConfigured();
        }

        foreach ($this->stages as $stage) {
            $deployment->getLogger()->notice('Stage ' . $stage);
            foreach ($nodes as $node) {
                $deployment->getLogger()->debug('Node ' . $node->getName());
                foreach ($applications as $application) {
                    if (! $application->hasNode($node)) {
                        continue;
                    }

                    $deployment->getLogger()->debug('Application ' . $application->getName());

                    try {
                        $this->executeStage($stage, $node, $application, $deployment);
                    } catch (DeploymentLockedException $exception) {
                        $deployment->setStatus(DeploymentStatus::CANCELLED());
                        $deployment->getLogger()->info($exception->getMessage());
                        if ($this->enableRollback) {
                            $this->taskManager->rollback();
                        }

                        return;
                    } catch (Exception $exception) {
                        $deployment->setStatus(DeploymentStatus::FAILED());
                        if ($this->enableRollback) {
                            if (array_search($stage, $this->stages, false) <= array_search('switch', $this->stages, false)) {
                                $deployment->getLogger()->error('Got exception "' . $exception->getMessage() . '" rolling back.');
                                $this->taskManager->rollback();
                            } else {
                                $deployment->getLogger()->error('Got exception "' . $exception->getMessage() . '" but after switch stage, no rollback necessary.');
                                $this->taskManager->reset();
                            }
                        } else {
                            $deployment->getLogger()->error('Got exception "' . $exception->getMessage() . '" but rollback disabled. Stopping.');
                        }

                        return;
                    }
                }
            }
        }
        if ($deployment->getStatus()->isUnknown()) {
            $deployment->setStatus(DeploymentStatus::SUCCESS());
        }
    }

    public function getName(): string
    {
        return 'Simple workflow';
    }

    public function setEnableRollback(bool $enableRollback): self
    {
        $this->enableRollback = $enableRollback;

        return $this;
    }

    public function isEnableRollback(): bool
    {
        return $this->enableRollback;
    }

    public function getStages(): array
    {
        return $this->stages;
    }
}
