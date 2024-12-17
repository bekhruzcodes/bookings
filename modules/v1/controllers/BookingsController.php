<?php

namespace app\modules\v1\controllers;

use yii\rest\Controller;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use app\modules\v1\components\CustomBearerAuth;
use app\modules\v1\models\Bookings;

class BookingsController extends Controller
{
    /**
     * Attach Bearer Token Authentication
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // Force JSON responses
        $behaviors['contentNegotiator']['formats']['application/json'] = Response::FORMAT_JSON;

        // Use Custom Bearer Authentication
        $behaviors['authenticator'] = [
            'class' => CustomBearerAuth::class,
        ];

        return $behaviors;
    }

    /**
     * Map actions to allowed HTTP methods
     */
    public function verbs()
    {
        return [
            'index' => ['GET'],
            'create' => ['POST'],
            'view' => ['GET'],
            'update' => ['PUT', 'PATCH'],
            'delete' => ['DELETE'],
        ];
    }

    /**
     * List all bookings for the authenticated website (GET).
     */
    public function actionIndex()
    {
        /** @var CustomBearerAuth $authenticator */
        $authenticator = $this->getBehavior('authenticator');
        $website = $authenticator->website;

        $bookings = $website->getBookings()->asArray()->all();

        return [
            'bookings' => $bookings,
        ];
    }

    /**
     * Create a new booking for the authenticated website (POST).
     */
    public function actionCreate()
    {
        $authenticator = $this->getBehavior('authenticator');
        $website = $authenticator->website;

        $model = new Bookings();
        $model->website_id = $website->id;

        if ($model->load(\Yii::$app->request->post(), '') && $model->save()) {
            return ['message' => 'Booking created successfully!', 'booking' => $model];
        }

        return ['error' => 'Failed to create booking', 'details' => $model->errors];
    }

    /**
     * View a single booking (GET).
     */
    public function actionView($id)
    {
        return ['booking' => $this->findBooking($id)];
    }

    /**
     * Update a booking (PUT).
     */
    public function actionUpdate($id)
    {
        $booking = $this->findBooking($id);

        if ($booking->load(\Yii::$app->request->bodyParams, '') && $booking->save()) {
            return ['message' => 'Booking updated successfully!', 'booking' => $booking];
        }

        return ['error' => 'Failed to update booking', 'details' => $booking->errors];
    }

    /**
     * Delete a booking (DELETE).
     */
    public function actionDelete($id)
    {
        $booking = $this->findBooking($id);

        if ($booking->delete()) {
            return ['message' => 'Booking deleted successfully!'];
        }

        throw new \yii\web\ServerErrorHttpException('Failed to delete the booking.');
    }

    /**
     * Finds a booking ensuring it belongs to the authenticated website.
     * @param integer $id
     * @return Bookings
     * @throws NotFoundHttpException
     */
    protected function findBooking($id)
    {
        $authenticator = $this->getBehavior('authenticator');
        $website = $authenticator->website;

        $booking = Bookings::findOne(['id' => $id, 'website_id' => $website->id]);

        if ($booking === null) {
            throw new NotFoundHttpException("The requested booking does not exist.");
        }

        return $booking;
    }
}
