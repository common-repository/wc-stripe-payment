<?php

// autoload_psr4.php @generated by Composer

$vendorDir = dirname(dirname(__FILE__));
$baseDir = dirname($vendorDir);

return array(
    'Stripe\\' => array($vendorDir . '/stripe/stripe-php/lib'),
    'PaymentWps\\WooFunnels\\Stripe\\' => array($baseDir . '/packages/woofunnels/src'),
    'PaymentWps\\CartFlows\\Stripe\\' => array($baseDir . '/packages/cartflows/src'),
    'PaymentWps\\Blocks\\Stripe\\' => array($baseDir . '/packages/blocks/src'),
);
