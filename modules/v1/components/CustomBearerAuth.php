<?php

namespace app\modules\v1\components;

use app\modules\v1\models\Websites;
use yii\filters\auth\AuthMethod;
use yii\web\UnauthorizedHttpException;

class CustomBearerAuth extends AuthMethod
{
    public $website;

    /**
     * Custom authentication logic
     * @throws UnauthorizedHttpException
     */
    public function authenticate($user, $request, $response)
    {
        $authHeader = $request->getHeaders()->get('Authorization');
        if ($authHeader !== null && preg_match('/^Bearer\s+(.*?)$/', $authHeader, $matches)) {
            $token = $matches[1];

            // Validate the token using the Websites model
            $this->website = Websites::findOne(['access_token' => $token]);
            if ($this->website) {
                return $this->website; // Return the authenticated Websites model
            }
        }

        // If token is invalid, throw Unauthorized exception
        throw new UnauthorizedHttpException('Your request was made with invalid credentials.');
    }
}
