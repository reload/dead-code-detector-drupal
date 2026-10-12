<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Core\Twig;

use Reload\DeadCodeDetectorDrupal\Core\Settings\SettingLists;

/**
 * The Twig sandbox as the site's settings configure it.
 *
 * @see \Drupal\Core\Template\TwigSandboxPolicy
 */
final class TwigSandbox
{
    private ?SandboxPolicy $policy = null;

    public function __construct(
        private readonly SettingLists $settingLists,
    ) {
    }

    /**
     * Read on first use.
     */
    public function policy(): SandboxPolicy
    {
        return $this->policy ??= SandboxPolicy::fromSettings($this->settingLists->lists([
            SandboxPolicy::ALLOWED_CLASSES_SETTING,
            SandboxPolicy::ALLOWED_METHODS_SETTING,
            SandboxPolicy::ALLOWED_PREFIXES_SETTING,
        ]));
    }
}
