<?php
namespace app\modules\v1\traits;
use Yii;

trait ResponseHelperTrait
{
    /**
     * Generalized response handling for bad requests.
     */
    private function badRequest(): array
    {
        Yii::$app->response->statusCode = 400;
        return [
            'status' => 'error',
            'message' => 'Invalid input data.',
        ];
    }

    /**
     * Generalized response handling for unauthorized errors.
     */
    private function unauthorizedRequest(): array
    {
        Yii::$app->response->statusCode = 401;
        return [
            'status' => 'error',
            'message' => 'Authentication failed.',
        ];
    }

    /**
     * Generalized response handling for not found errors.
     */
    private function notFound(): array
    {
        Yii::$app->response->statusCode = 404;
        return [
            'status' => 'error',
            'message' => 'Resource not found.',
        ];
    }

    /**
     * Generalized response handling for unprocessable entity errors.
     */
    private function unprocessableEntity($model): array
    {
        Yii::$app->response->statusCode = 422;
        return [
            'status' => 'error',
            'errors' => $model->errors,
        ];
    }

    /**
     * Generalized response handling for internal server errors.
     */
    private function internalServerError(): array
    {
        Yii::$app->response->statusCode = 500;
        return [
            'status' => 'error',
            'message' => 'An unexpected error occurred.',
        ];
    }

    /**
     * Generalized response handling for successful responses.
     */
    private function successResponse($data): array
    {
        Yii::$app->response->statusCode = 200;
        return [
            'status' => 'success',
            'data' => $data,
        ];
    }

    /**
     * Generalized response handling for created resource.
     */
    private function createdResponse($data): array
    {
        Yii::$app->response->statusCode = 201;
        return [
            'status' => 'success',
            'data' => $data,
            'message' => 'Resource created successfully.',
        ];
    }

    /**
     * Generalized response handling for no content response.
     */
    private function noContentResponse(): array
    {
        Yii::$app->response->statusCode = 204;
        return [
            'status' => 'success',
            'message' => 'No content.',
        ];
    }
}
