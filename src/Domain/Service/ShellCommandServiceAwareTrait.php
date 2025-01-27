<?php
namespace TYPO3\Surf\Domain\Service;

/*
 * This file is part of TYPO3 Surf.
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */
/**
 * A shell command service aware class
 */
trait ShellCommandServiceAwareTrait
{
    protected ShellCommandService $shell;

    public function setShellCommandService(ShellCommandService $shellCommandService): void
    {
        $this->shell = $shellCommandService;
    }
}
