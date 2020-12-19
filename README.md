# Laravel storage file api

## Cài đặt
> Yêu cầu đã cài php > 7.0, composer , mysql
- ``` composer install ```
- ``` cp .env.example .env ``` tạo file env
- ``` php artisan migration ``` khởi tạo table (chú ý biến connect mysql trong file .env và API_AUDIO_TOKEN)
- ``` API_AUDIO_TOKEN ``` : Token auth connect tới <a href="https://audd.io">audd.io</a> (AudD Music Recognition API)
- ``` php artisan db:seed --class=eToken ``` khởi tạo etoken dùng để xác thực (token được tạo qua seed sẽ có thời hạn 1 tháng kể từ ngày tạo)