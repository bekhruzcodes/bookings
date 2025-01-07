<?php
use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title = 'Bron qilish uchun API';
?>

    <div class="documentation">
        <h1><?= Html::encode($this->title) ?></h1>

        <p>Yii Basic template asosida yaratilgan RESTful API bo'lib, turli veb-saytlar uchun bron qilishlarni boshqaradi. Bu API ro'yxatdan o'tgan veb-saytlarga bron qilish bo'yicha CRUD operatsiyalarini bajarishga va vaqt oraliqlarining to'qnashuvini oldini olishga imkon beradi.</p>

        <div class="section">
            <h2>Imkoniyatlar</h2>
            <ul>
                <li>Veb-sayt ro'yxatdan o'tkazish va autentifikatsiya</li>
                <li>Bron qilishni boshqarish (CRUD operatsiyalari)</li>
                <li>Avtomatik vaqt oralig'i tekshiruvi</li>
                <li>Veb-saytning bron qilish statistikasi</li>
                <li>Sahifalashni qo'llab-quvvatlashi</li>
                <li>RESTful arxitektura asosida</li>
            </ul>
        </div>

        <div class="section">
            <h2>Autentifikatsiya</h2>
            <p>Barcha API endpointlari Bearer token autentifikatsiyasini talab qiladi:</p>
            <ol>
                <li>Ro'yxatdan o'tish endpointi orqali veb-saytingizni ro'yxatdan o'tkazing</li>
                <li>Olingan tokeni <code>Authorization: Bearer &lt;token&gt;</code> sarlavhasi orqali so'rovlarga qo'shing</li>
            </ol>
        </div>

        <div class="section">
            <h2>API Endpointlar</h2>

            <div class="endpoint">
                <h3>Veb-saytni ro'yxatdan o'tkazish</h3>
                <span class="method post">POST</span>
                <span class="url">https://bookings.bekhruzbek.uz/v1/websites/register</span>
                <pre>{
    "name": "Veb-saytingiz nomi",
    "email": "contact@yourwebsite.com"
}</pre>
            </div>

            <div class="endpoint">
                <h3>Bron qilish</h3>
                <span class="method post">POST</span>
                <span class="url">https://bookings.bekhruzbek.uz/v1/bookings</span>
                <pre>{
    "service_name": "Premium Xizmat",
    "customer_name": "Javlon Aliyev",
    "customer_contact": "998908887766",
    "booking_date": "2024-10-30",
    "start_time": "12:00",
    "end_time": "12:30",
    "duration_minutes": "30"
}</pre>
            </div>

            <div class="endpoint">
                <h3>Veb-sayt statistikasi</h3>
                <span class="method get">GET</span>
                <span class="url">https://bookings.bekhruzbek.uz/v1/bookings/statistics</span>
                <p>Javob formati:</p>
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
            "day": "Chorshanba",
            "count": 1,
            "percentage": 33.33
        },
        "mostSellingDuration": {
            "durationMinutes": 30,
            "count": 3,
            "percentage": 100
        },
        "mostSellingService": {
            "serviceName": "Premium Xizmat",
            "count": 2,
            "percentage": 66.67
        },
        "returnClients": {
            "count": 1,
            "details": [
                {
                    "customerContact": "998908887766",
                    "customerName": "Javlon Aliyev",
                    "bookings": 3
                }
            ]
        }
    }
}</pre>
            </div>
        </div>

        <div class="section">
            <h2>Boshqa Endpointlar</h2>
            <table>
                <thead>
                <tr>
                    <th>Endpoint</th>
                    <th>Metod</th>
                    <th>Tavsif</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td><code>/v1/bookings/{id}</code></td>
                    <td><span class="method get">GET</span></td>
                    <td>Bitta bron qilishni olish</td>
                </tr>
                <tr>
                    <td><code>/v1/bookings/{id}</code></td>
                    <td><span class="method put">PUT</span></td>
                    <td>Bron qilishni yangilash</td>
                </tr>
                <tr>
                    <td><code>/v1/bookings/{id}</code></td>
                    <td><span class="method delete">DELETE</span></td>
                    <td>Bron qilishni o'chirish</td>
                </tr>
                </tbody>
            </table>
        </div>

        <div class="section">
            <h2>Biznes Qoidalar</h2>
            <ul>
                <li>
                    <strong>Vaqt oralig'i tekshiruvi:</strong>
                    <ul>
                        <li>Har bir vaqt oralig'i bir kunda bir veb-sayt uchun faqat bir marta bron qilinishi mumkin</li>
                        <li>Bir xil veb-sayt uchun bir kunda ustma-ust tushadigan bron qilishlarga ruxsat berilmaydi</li>
                    </ul>
                </li>
                <li>
                    <strong>Autentifikatsiya:</strong>
                    <ul>
                        <li>Veb-sayt ro'yxatdan o'tkazishdan tashqari barcha endpointlar Bearer token talab qiladi</li>
                        <li>Veb-sayt ID si autentifikatsiya tokeni asosida avtomatik tayinlanadi</li>
                    </ul>
                </li>
            </ul>
        </div>

        <div class="section">
            <h2>O'rnatish</h2>
            <ol>
                <li>
                    Yangi Yii Basic loyiha yarating:
                    <pre>composer create-project --prefer-dist yiisoft/yii2-app-basic booking-api</pre>
                </li>
                <li>
                    Repozitoriyani remote sifatida qo'shing va pull qiling:
                    <pre>git remote add origin https://github.com/bekhruzcodes/bookings.git
git pull origin main</pre>
                </li>
                <li>
                    Quyidagi fayllarni muhitingizga moslab sozlang:
                    <ul>
                        <li><code>config/db.php</code></li>
                        <li><code>config/web.php</code></li>
                        <li><code>config/console.php</code></li>
                        <li><code>config/params.php</code></li>
                    </ul>
                </li>
                <li>
                    Migratsiyalarni ishga tushiring:
                    <pre>php yii migrate</pre>
                </li>
            </ol>
        </div>
    </div>

<?php
