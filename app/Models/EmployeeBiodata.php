<?php

namespace App\Models;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EmployeeBiodata extends Model
{
    private $api;
    private $client;

    public function __construct()
    {
        $this->api = env('URL_SERVICE_REGION');
        $this->client = new Client();
    }

    use HasFactory;

    protected $fillable = [
        'employee_id',
        'fullname',
        'nickname',
        'nik',
        'identity_province',
        'identity_regency',
        'identity_district',
        'identity_village',
        'identity_postal_code',
        'identity_address',
        'current_province',
        'current_regency',
        'current_district',
        'current_village',
        'current_postal_code',
        'current_address',
        'residence_status',
        'phone_number',
        'emergency_phone_number',
        'start_work',
        'gender',
        'birth_place',
        'birth_date',
        'religion',
        'blood_type',
        'weight',
        'height',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $appends = [
        'identity_full_address',
        'current_full_address',
        'age',
    ];

    public function getIdentityFullAddressAttribute()
    {
        try {
            // province
            $provinceData = $this->client->get("$this->api/province/" . $this->attributes['identity_province']);
            $body = $provinceData->getBody()->getContents();
            $provinceRes = json_decode($body, true);
            $province = '';
            if($provinceRes['status'] == 'success') $province = Str::title($provinceRes['data']['name']);

            // regency
            $regencyData = $this->client->get("$this->api/regency/" . $this->attributes['identity_regency']);
            $body = $regencyData->getBody()->getContents();
            $regencyRes = json_decode($body, true);
            $regency = '';
            if($regencyRes['status'] == 'success') $regency = Str::title($regencyRes['data']['name']);

            // district
            $districtData = $this->client->get("$this->api/district/" . $this->attributes['identity_district']);
            $body = $districtData->getBody()->getContents();
            $districtRes = json_decode($body, true);
            $district = '';
            if($districtRes['status'] == 'success') $district = Str::title($districtRes['data']['name']);

            // village
            $villageData = $this->client->get("$this->api/village/" . $this->attributes['identity_village']);
            $body = $villageData->getBody()->getContents();
            $villageRes = json_decode($body, true);
            $village = '';
            if($villageRes['status'] == 'success') $village = Str::title($villageRes['data']['name']);

            return $this->attributes['identity_address'] . ", $village, $district, $regency, $province";

        } catch (ClientException $e) {
            return null;
        }
    }

    public function getCurrentFullAddressAttribute()
    {
        try {
            // province
            $provinceData = $this->client->get("$this->api/province/" . $this->attributes['current_province']);
            $body = $provinceData->getBody()->getContents();
            $provinceRes = json_decode($body, true);
            $province = '';
            if($provinceRes['status'] == 'success') $province = Str::title($provinceRes['data']['name']);

            // regency
            $regencyData = $this->client->get("$this->api/regency/" . $this->attributes['current_regency']);
            $body = $regencyData->getBody()->getContents();
            $regencyRes = json_decode($body, true);
            $regency = '';
            if($regencyRes['status'] == 'success') $regency = Str::title($regencyRes['data']['name']);

            // district
            $districtData = $this->client->get("$this->api/district/" . $this->attributes['current_district']);
            $body = $districtData->getBody()->getContents();
            $districtRes = json_decode($body, true);
            $district = '';
            if($districtRes['status'] == 'success') $district = Str::title($districtRes['data']['name']);

            // village
            $villageData = $this->client->get("$this->api/village/" . $this->attributes['current_village']);
            $body = $villageData->getBody()->getContents();
            $villageRes = json_decode($body, true);
            $village = '';
            if($villageRes['status'] == 'success') $village = Str::title($villageRes['data']['name']);

            return $this->attributes['current_address'] . ", $village, $district, $regency, $province";

        } catch (ClientException $e) {
            return null;
        }
    }

    public function getAgeAttribute()
    {
        return ageCount($this->attributes['birth_date']);
    }
}
