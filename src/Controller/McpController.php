<?php

namespace App\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class McpController
{
    public function handle(Request $request, Response $response): Response
    {
        $body = (string) $request->getBody();
        $data = json_decode($body, true);

        if (!is_array($data)) {
            return $this->jsonRpcError(
                $response,
                null,
                -32700,
                'Parse error'
            );
        }

        $method = $data['method'] ?? null;
        $id = $data['id'] ?? null;
        $params = $data['params'] ?? [];

        switch ($method) {

            /*
             * ==========================
             * MCP INITIALIZE
             * ==========================
             */
            case 'initialize':

                return $this->jsonResponse($response, [
                    'jsonrpc' => '2.0',
                    'id' => $id,
                    'result' => [
                        'protocolVersion' => '2025-06-18',

                        'capabilities' => [
                            'tools' => [
                                'listChanged' => false
                            ]
                        ],

                        'serverInfo' => [
                            'name' => 'slim-product-mcp',
                            'version' => '1.0.0'
                        ]
                    ]
                ]);


                /*
             * ==========================
             * LIST TOOLS
             * ==========================
             */
            case 'tools/list':

                return $this->jsonResponse($response, [
                    'jsonrpc' => '2.0',
                    'id' => $id,
                    'result' => [
                        'tools' => [

                            [
                                'name' => 'get_products',

                                'description' =>
                                'Mengambil daftar produk',

                                'inputSchema' => [
                                    'type' => 'object',

                                    'properties' => new \stdClass()
                                ]
                            ]

                        ]
                    ]
                ]);


                /*
             * ==========================
             * CALL TOOL
             * ==========================
             */
            case 'tools/call':

                $toolName = $params['name'] ?? null;

                switch ($toolName) {

                    case 'get_products':

                        // sementara dummy
                        $products = [
                            [
                                'id' => 1,
                                'name' => 'Laptop',
                                'price' => 10000000
                            ],
                            [
                                'id' => 2,
                                'name' => 'Mouse',
                                'price' => 200000
                            ]
                        ];

                        return $this->jsonResponse($response, [
                            'jsonrpc' => '2.0',
                            'id' => $id,

                            'result' => [
                                'content' => [
                                    [
                                        'type' => 'text',
                                        'text' => json_encode(
                                            $products,
                                            JSON_PRETTY_PRINT
                                        )
                                    ]
                                ]
                            ]
                        ]);

                    default:

                        return $this->jsonRpcError(
                            $response,
                            $id,
                            -32602,
                            'Unknown tool'
                        );
                }


            default:

                return $this->jsonRpcError(
                    $response,
                    $id,
                    -32601,
                    'Method not found'
                );
        }
    }


    private function jsonResponse(
        Response $response,
        array $data
    ): Response {

        $response->getBody()->write(
            json_encode($data)
        );

        return $response
            ->withHeader(
                'Content-Type',
                'application/json'
            );
    }


    private function jsonRpcError(
        Response $response,
        $id,
        int $code,
        string $message
    ): Response {

        return $this->jsonResponse($response, [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => $code,
                'message' => $message
            ]
        ]);
    }
}
