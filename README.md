# Booking API Documentation

A RESTful API built with Yii Basic template for managing bookings across multiple websites. This API allows registered websites to perform CRUD operations on bookings while preventing time slot overlaps.

## Features

- Website registration and authentication
- Booking management (CRUD operations)
- Automatic time slot validation
- RESTful architecture
- Pagination support

## Authentication

All API endpoints require Bearer token authentication. To obtain a token:

1. Register your website using the registration endpoint
2. Include the received token in subsequent requests using the `Authorization: Bearer <token>` header

### Website Registration

```http
POST https://bekhruzbek.uz/v1/websites/register
```

Request body:
```json
{
    "name": "Your Website Name",
    "email": "contact@yourwebsite.com"
}
```

Response will include your Bearer token for authentication and your website ID.

## API Endpoints

### Create Booking

```http
POST https://bekhruzbek.uz/v1/bookings
```

Request body:
```json
{
    "website_id": "1",
    "service_name": "Premium Service",
    "customer_name": "John Doe",
    "customer_contact": "998908887766",
    "booking_date": "2024-10-30",
    "start_time": "12:00",
    "end_time": "14:00",
    "duration_minutes": "30"
}
```

### List Bookings

```http
GET https://bekhruzbek.uz/v1/bookings
```

Response format:
```json
{
    "_meta": {
        "totalCount": 1,
        "pageCount": 1,
        "currentPage": 1,
        "perPage": 10
    },
    "_links": {
        "self": "https://bekhruzbek.uz/v1/bookings?page=1&per-page=10",
        "next": null,
        "prev": null
    },
    "data": [
        {
            "id": 1,
            "website_id": 1,
            "service_name": "Premium Service",
            "customer_name": "John Doe",
            "customer_contact": "998908887766",
            "booking_date": "2024-10-30",
            "start_time": "12:00:00",
            "end_time": "12:30:00",
            "duration_minutes": 30,
            "created_at": "2024-12-28 17:09:36"
        }
    ]
}
```

### Get Single Booking

```http
GET https://bekhruzbek.uz/v1/bookings/{id}
```

### Update Booking

```http
PUT https://bekhruzbek.uz/v1/bookings/{id}
```
or
```http
PATCH https://bekhruzbek.uz/v1/bookings/{id}
```

### Delete Booking

```http
DELETE https://bekhruzbek.uz/v1/bookings/{id}
```

## Business Rules

1. Time slot validation:
   - Each time slot can only be booked once per day per website
   - No overlapping bookings are allowed for the same website on the same day

2. Authentication:
   - All endpoints except website registration require valid Bearer token
   - Each website must use their own unique token

## Pagination

The API supports pagination with the following query parameters:
- `page`: Page number (default: 1)
- `per-page`: Items per page (default: 10)

Example:
```http
GET https://bekhruzbek.uz/v1/bookings?page=2&per-page=20
```

## Error Handling

The API returns appropriate HTTP status codes:
- 200: Success
- 201: Created
- 400: Bad Request
- 401: Unauthorized
- 403: Forbidden
- 404: Not Found
- 422: Data Validation Failed
- 500: Internal Server Error

## Installation

1. Clone the repository
2. Install dependencies using Composer:
   ```bash
   composer install
   ```
3. Configure your database settings in `config/db.php`
4. Run migrations:
   ```bash
   php yii migrate
   ```

## Contributing

We welcome contributions! Please feel free to submit a Pull Request.

## License

This project is open source and available under the [MIT License](LICENSE).
