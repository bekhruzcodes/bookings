<?php

namespace app\modules\v1\controllers;


use Yii;
use yii\db\Exception;
use yii\filters\ContentNegotiator;
use yii\rest\Controller;
use yii\web\Response;
use app\modules\v1\models\Websites;

/**
 * WebsiteController handles REST API for website registration.
 */
class WebsitesController extends Controller
{
    public function behaviors(): array
    {
        return [
            'contentNegotiator' => [
                'class' => ContentNegotiator::class,
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ],
            ],
        ];
    }

    /**
     * Register a new website and return access token.
     * Method: POST
     * URL: /website/register
     * @throws Exception
     */
    public function actionRegister(): array
    {
        $request = Yii::$app->request;
        $response = Yii::$app->response;
        $website = new Websites();

        // Load input data
        $website->load($request->post(), '');
        $website->generateAccessToken();

        // Check if input is valid
        if ($website->validate()) {
            // Save website to database
            if ($website->save()) {
                return [
                    'success' => true,
                    'message' => 'Website registered successfully.',
                    'data' => [
                        'id' => $website->id,
                        'name' => $website->name,
                        'email' => $website->email,
                        'access_token' => $website->access_token,
                        'created_at' => date('Y-m-d H:i:s'),
                    ],
                ];
            } else {
                $response->statusCode = 500; // Internal Server Error
                return [
                    'success' => false,
                    'message' => 'Failed to save website data.',
                ];
            }
        } else {
            $response->statusCode = 422; // Set status code for data validation failure
            return [
                'success' => false,
                'message' => 'Data Validation Failed.',
                'errors' => $website->errors,
            ];
        }
    }


}
