


<div class="documentation">
    <h1>Booking API Documentation</h1>
    <p>A RESTful API built with Yii Basic template for managing bookings across multiple websites. This API allows registered websites to perform CRUD operations on bookings while preventing time slot overlaps.</p>
    <div class="section">
        <h2>Features</h2>
        <ul>
            <li>Website registration and authentication</li>
            <li>Booking management (CRUD operations)</li>
            <li>Automatic time slot validation</li>
            <li>RESTful architecture</li>
            <li>Pagination support</li>
            <li>Website-specific statistics</li>
        </ul>
    </div>


    <div class="section">
        <h2>Authentication</h2>
        <p>All API endpoints require Bearer token authentication. To obtain a token:</p>
        <ol>
            <li>Register your website using the registration endpoint</li>
            <li>Include the received token in subsequent requests using the <code>Authorization: Bearer &lt;token&gt;</code> header</li>
        </ol>
    </div>

    <div class="section">
        <h2>API Endpoints</h2>

        <div class="endpoint">
            <h3>Website Registration</h3>
            <span class="method post">POST</span>
            <span class="url">https://bookings.bekhruzbek.uz/v1/websites/register</span>
            <pre>{
    "name": "Your Website Name",
    "email": "contact@yourwebsite.com"
}</pre>
        </div>

        <div class="endpoint">
            <h3>Create Booking</h3>
            <span class="method post">POST</span>
            <span class="url">https://bookings.bekhruzbek.uz/v1/bookings</span>
            <pre>{
    "service_name": "Premium Service",
    "customer_name": "John Doe",
    "customer_contact": "998908887766",
    "booking_date": "2024-10-30",
    "start_time": "12:00",
    "end_time": "14:00",
    "duration_minutes": "30"
}</pre>
        </div>

        <div class="endpoint">
            <h3>Website Statistics</h3>
            <span class="method get">GET</span>
            <span class="url">https://bookings.bekhruzbek.uz/v1/bookings/statistics</span>
            <p>Response format:</p>
            <pre>{
    "status": "success",
    "data": {
        "totalBookings": {
            "count": 3,
            "percentage": 100
        },
        "mostSellingTime": {
            "hour": 14,
            "count": 2,
            "percentage": 66.67
        },
        "mostSellingDay": {
            "day": "Wednesday",
            "count": 1,
            "percentage": 33.33
        },
        "mostSellingDuration": {
            "durationMinutes": 30,
            "count": 3,
            "percentage": 100
        },
        "mostSellingService": {
            "serviceName": "Premium Service",
            "count": 2,
            "percentage": 66.67
        },
        "returnClients": {
            "count": 1,
            "details": [
                {
                    "customerContact": "998908887766",
                    "customerName": "John Doe",
                    "bookings": 3
                }
            ]
        }
    }
}</pre>
        </div>
    </div>

    <div class="section">
        <h2>Other Endpoints</h2>
        <table>
            <thead>
            <tr>
                <th>Endpoint</th>
                <th>Method</th>
                <th>Description</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td><code>/v1/bookings/{id}</code></td>
                <td><span class="method get">GET</span></td>
                <td>Get single booking</td>
            </tr>
            <tr>
                <td><code>/v1/bookings/{id}</code></td>
                <td><span class="method put">PUT</span></td>
                <td>Update booking</td>
            </tr>
            <tr>
                <td><code>/v1/bookings/{id}</code></td>
                <td><span class="method delete">DELETE</span></td>
                <td>Delete booking</td>
            </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Business Rules</h2>
        <ul>
            <li>
                <strong>Time slot validation:</strong>
                <ul>
                    <li>Each time slot can only be booked once per day per website</li>
                    <li>No overlapping bookings are allowed for the same website on the same day</li>
                </ul>
            </li>
            <li>
                <strong>Authentication:</strong>
                <ul>
                    <li>All endpoints except website registration require valid Bearer token</li>
                    <li>Website ID is automatically assigned based on authentication token</li>
                </ul>
            </li>
        </ul>
    </div>

    <div class="section">
        <h2>Installation</h2>
        <ol>
            <li>
                Create a new Yii Basic project:
                <pre>composer create-project --prefer-dist yiisoft/yii2-app-basic booking-api</pre>
            </li>
            <li>
                Add the repository as remote and pull:
                <pre>git remote add origin https://github.com/bekhruzcodes/bookings.git
git pull origin main</pre>
            </li>
            <li>
                Configure the following files according to your environment:
                <ul>
                    <li><code>config/db.php</code></li>
                    <li><code>config/web.php</code></li>
                    <li><code>config/console.php</code></li>
                    <li><code>config/params.php</code></li>
                </ul>
            </li>
            <li>
                Run migrations:
                <pre>php yii migrate</pre>
            </li>
        </ol>
    </div>
</div>
