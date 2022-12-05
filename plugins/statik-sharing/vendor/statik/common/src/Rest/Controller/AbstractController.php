<?php

declare(strict_types=1);

namespace Statik\Common\Rest\Controller;

use Statik\Common\Config\ConfigInterface;
use Statik\Common\Rest\ApiInterface;

/**
 * Class AbstractController.
 */
abstract class AbstractController extends \WP_REST_Controller implements ControllerInterface
{
    protected ApiInterface $api;

    protected ConfigInterface $config;

    /**
     * AbstractController constructor.
     */
    public function __construct(ApiInterface $api)
    {
        $this->api = $api;
    }

    /**
     * Check if current user has correct capabilities.
     */
    public function checkPermissions(\WP_REST_Request $request): bool
    {
        return \current_user_can('manage_options');
    }

    /**
     * Check if parameter is type of numeric.
     *
     * @param mixed $param
     */
    public function isNumeric($param): bool
    {
        return \is_numeric($param);
    }
}
