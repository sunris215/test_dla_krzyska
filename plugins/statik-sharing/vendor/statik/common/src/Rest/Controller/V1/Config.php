<?php

declare(strict_types=1);

namespace Statik\Common\Rest\Controller\V1;

use Statik\Common\Config\AbstractConfig;
use Statik\Common\Helper\Callback;
use Statik\Common\Rest\ApiInterface;
use Statik\Common\Rest\Controller\AbstractController;

/**
 * Class Config.
 */
class Config extends AbstractController
{
    /**
     * {@inheritdoc}
     */
    public function __construct(ApiInterface $api)
    {
        parent::__construct($api);
        $this->rest_base = 'config';
    }

    /**
     * {@inheritdoc}
     */
    public function registerRoutes(): void
    {
        \register_rest_route(
            $this->api->getNamespace(),
            "/{$this->rest_base}",
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'getConfig'],
                'permission_callback' => [$this, 'checkPermissions'],
            ]
        );

        \register_rest_route(
            $this->api->getNamespace(),
            "/{$this->rest_base}",
            [
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'setConfig'],
                'permission_callback' => [$this, 'checkPermissions'],
                'args'                => [
                    'name'  => [
                        'required' => true,
                        'type'     => 'string',
                    ],
                    'value' => [
                        'required' => true,
                    ],
                ],
            ]
        );

        \register_rest_route(
            $this->api->getNamespace(),
            "/{$this->rest_base}/async-select/(?P<name>[\\w\\-\\.]++)",
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'getAsyncSelectValues'],
                'permission_callback' => [$this, 'checkPermissions'],
                'args'                => [
                    'name' => [
                        'required' => true,
                        'type'     => 'string',
                    ],
                ],
            ]
        );
    }

    /**
     * Get all plugin Config as array.
     */
    public function getConfig(\WP_REST_Request $request): \WP_REST_Response
    {
        foreach (AbstractConfig::getInstances() as $name => $instance) {
            $config[$name] = $instance->toArray();
        }

        return new \WP_REST_Response($config ?? []);
    }

    /**
     * Get options for async properties in Settings selects. Controller return
     * array with all available options or error when values do not exist
     * or select has not to option to async call.
     */
    public function getAsyncSelectValues(\WP_REST_Request $request)
    {
        $fieldName = (string) $request->get_param('name');

        foreach (AbstractConfig::getInstances() as $instance) {
            if ($instance->has($fieldName)) {
                $field = $instance->get($fieldName, null);
            }
        }

        if (false === isset($field) || false === \is_array($field)) {
            return new \WP_Error(
                'rest_statik_invalid_name',
                \__("The field `{$fieldName}` does not exists.", 'statik'),
                ['status' => 404]
            );
        }

        foreach (Callback::getResults($field) as $key => $value) {
            $data[] = [
                'text'  => $value,
                'value' => $key,
            ];
        }

        return new \WP_REST_Response($data ?? []);
    }
}
