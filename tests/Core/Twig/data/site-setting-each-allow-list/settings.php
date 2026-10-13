<?php

// Each allows only what core's defaults reject; prefixes match case-sensitively, unlike core's "get".
$settings['twig_sandbox_allowed_classes'] = ['Reload\DeadCodeDetectorDrupal\Tests\Core\Twig\Sandbox\ClassOnAllowList'];
$settings['twig_sandbox_allowed_methods'] = ['renew'];
$settings['twig_sandbox_allowed_prefixes'] = ['Get'];
