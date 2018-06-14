<?php

namespace App\Repositories\API;

use Carbon\Carbon;
use App\Models\Auth\User;
use Illuminate\Http\UploadedFile;
use App\Models\Auth\SocialAccount;
use Illuminate\Support\Facades\DB;
use App\Exceptions\GeneralException;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Events\Frontend\Auth\UserConfirmed;
use App\Events\Frontend\Auth\UserProviderRegistered;
use App\Notifications\Frontend\Auth\UserNeedsConfirmation;

/**
 * Class UserRepository.
 */
class UserRepository extends BaseRepository
{
    /**
     * @return string
     */
    public function model()
    {
        return User::class;
    }

    /**
     * @param $uuid
     *
     * @return mixed
     * @throws GeneralException
     */
    public function findByUuid($uuid)
    {
        $user = $this->model
            ->uuid($uuid)
            ->first();

        if ($user instanceof $this->model) {
            return $user;
        }

        throw new GeneralException(__('exceptions.backend.access.users.not_found'));
    }

    /**
     * @param $email
     *
     * @return mixed
     * @throws GeneralException
     */
    public function findByEmail($email)
    {
        $user = $this->model
            ->where('email', $email)
            ->first();

        if ($user instanceof $this->model) {
            return $user;
        }

        throw new GeneralException(__('exceptions.backend.access.users.not_found'));
    }

    /**
     * @param array $data
     *
     * @return \Illuminate\Database\Eloquent\Model|mixed
     * @throws \Exception
     * @throws \Throwable
     */
    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $user = parent::create([
                'first_name'          => $data['firstName'],
                'last_name'           => $data['lastName'],
                'email'               => $data['email'],
                'phone_number'        => $data['phoneNumber'],
                'password'            => $data['password'],
                'dob'                 => $data['dob'],
                'gender'              => $data['gender'],
                'address1'            => $data['address1'],
                'address2'            => $data['address2'],
                'address3'            => $data['address3'],
                'city'                => $data['city'],
                'state'               => $data['state'],
                'country'             => $data['country'],
                'pin'                 => $data['pin'],
                'aadhaar_number'      => $data['aadhaarNumber'],
                'profile_photos'      => $data['profilePhoto'] ? $this->generateFileData($data['profilePhoto'], true) : NULL,
                'qualification'       => $data['qualification'],
                'institute'           => $data['institute'],
                'score'               => $data['score'],
                'certificate'         => $data['uploadCertificate'] ? $this->generateFileData($data['uploadCertificate'], false) : NULL,
                'area_of_expertise'   => $data['areaOfExpertise'],
                'experience'          => $data['experience'],
                'project_worked_with' => $data['projectWorkedWith'],
                'agreed_terms'        => $data['agreeTerms'],
                'confirmation_code'   => md5(uniqid(mt_rand(), true)),
                'active'              => 1,
                                      // If users require approval or needs to confirm email
                'confirmed'           => 1,
            ]);

            if ($user) {
                /*
                 * Add the default site role to the new user
                 */
                $user->assignRole(config('access.users.default_role'));
            }

            /*
             * If users have to confirm their email and this is not a social account,
             * and the account does not require admin approval
             * send the confirmation email
             *
             * If this is a social account they are confirmed through the social provider by default
             */
            /* if (config('access.users.confirm_email')) {
                // Pretty much only if account approval is off, confirm email is on, and this isn't a social account.
                $user->notify(new UserNeedsConfirmation($user->uuid));
            } */

            /*
             * Return the user object
             */
            return $user;
        });
    }

    public function generateFileData($fileData, $type) 
    {
        $file_names = [];
        foreach ($fileData as $key => $value) {
          $image_data = base64_decode($value['data']);
          $image_type = explode('/', $value['type']);  
          $file_name = uniqid().'.'.$image_type[1];
          $file_names[$key] = $file_name;  
          Storage::disk('public')->put($file_name, $image_data);
        }
        if ($type) {
           return implode(';', $file_names);
        }
        return $file_name;
    }
}
