<?php

namespace App\Http\Controllers;

use App\Http\Requests\addApartmentRequest;
use App\Http\Requests\offerApartmentRequest;
use App\Models\Apartment;
use App\Models\ApartmentUser;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class ApartmentController extends Controller
{
    public function addApartment(addApartmentRequest $request)
    {

        $validated = $request->validated();
        $apartment = Apartment::create($validated);
        $apartment->users()->attach(
            Auth::id(),
            [
                'enType'    => 'Owner',
                'enStatus'  => null,
                'rate' => null,
                'startTerm' => null,
                'endTerm'   => null,
            ]
        );
        return response()->json(['mes' => "apartment added successfully ", 'data' => $apartment]);
    }

    public function offerApartment(offerApartmentRequest $request, int $id)
    {
        $validated = $request->validated();
        $apartment = Apartment::where('id', '=', $id)->firstOrFail();
        $isAvailable = $this->checkIFreserved($validated['startTerm'], $validated['endTerm'], $apartment);
        if (!$isAvailable) {
            //
            return response()->json(['mes' => "Please check the availability then choose alternative dates ", 'data' => null]);
        }
        $apartment->users()->attach(
            Auth::id(),
            [
                'enType'    => 'Renter',
                'enStatus'  => 'Pending',
                'rate' => null,
                'startTerm' => $validated['startTerm'],
                'endTerm'   => $validated['endTerm'],
            ]
        );


        $temp = ApartmentUser::where('apartment_id', '=', $id)->where('enType', 'Owner')->firstOrFail();
        $owner_id = $temp['user_id'];
        Notification::create([
            'user_id' => $owner_id,
            'type' => " to do ....",
            'data' => [
                'title' => " new offer to your apartment ",
                'apartment_id' => $id,
                'user_id' => Auth::id(),
                'startTerm' => $temp['startTerm'],
                'endTerm' => $temp['endTerm']
            ]
        ]);
                return response()->json(['mes' => "Your offer has been successfully submitted to the apartment owner
please wait for their approval ", 'data' => null]);
    }

    public function EvaluateApartment(int $id, int $number)
    {
        $apartmentuser = ApartmentUser::where('user_id', '=', Auth::id())->where('apartment_id', '=', $id)
            ->where('enType', '=', 'Renter')->where('enStatus', '=', 'Accepted')->firstOrFail();
        if ($number < 0 || $number > 5) {
            //...
        }
        $mid_date = (Carbon::parse($apartmentuser['startTerm'])->startOfDay())->average(Carbon::parse($apartmentuser['endTerm']));
        if (!($mid_date->lt(now()))) {
            //...
        }
        $apartmentuser['rate'] = $number;
        $apartmentuser->save();
        
        $temp = ApartmentUser::where('apartment_id', '=', $id)->where('enType', 'Owner')->firstOrFail();
        $owner_id = $temp['user_id'];
        Notification::create([
            'user_id' => $owner_id,
            'type' => " to do ....",
            'data' => [
                'title' => " new Evaluate to your apartment ",
                'apartment_id' => $id,
                'user_id' => Auth::id(),
                'rate' => $number
            ]
        ]);
        return response()->json(['mes' => "EvaluateApartment has been successfully ", 'data' => null]);
    }
    /*     Schema::create('apartment_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('apartment_id')->nullable()->constrained('apartments')->nullOnDelete();
            $table->enum('enType', ['Owner', 'Renter']);
            $table->enum('enStatus', ['Pending', 'Cancled', 'Accepted'])->nullable();
            $table->float('rate')->nullable();
            $table->date('startTerm')->nullable();
            $table->date('endTerm')->nullable();
            $table->timestamps();
        });*/
    public function checkIFreserved(string $start, string $end, Apartment $apartment)
    {
        $start_date = Carbon::parse($start)->startOfDay();
        $end_date   = Carbon::parse($end)->endOfDay();
        $apartmentuser = ApartmentUser::where('enType', 'Renter')->whereIn('enStatus', ['Accepted', 'Pending'])->where('apartment_id', '=', $apartment->id)->orderBy('startTerm', 'asc')->get();
        if ($apartmentuser->isEmpty()) {
            return true;
        }
        $x = Carbon::parse($apartmentuser[0]->startTerm)->startOfDay();
        if ($end_date->lt($x)) {
            return true;
        }
        for ($i = 0; $i < count($apartmentuser); $i++) {
            $end_res = Carbon::parse($apartmentuser[$i]->endTerm)->endOfDay();

            $next_res = $apartmentuser[$i + 1] ?? null;

            if (!$next_res) {
                if ($start_date->gt($end_res)) {
                    return true;
                }
                return false;
            }
            $next_res_start = Carbon::parse($next_res->startTerm)->startOfDay();
            if (
                $start_date->gt($end_res)  &&
                $end_date->lt($next_res_start)
            ) {
                return true;
            }
        }

        return false;
    }


    public function Show_Reservations(int $id)
    {
        $apartment = Apartment::where('id', '=', $id)->firstOrFail();
    
        $reservationsOnApartment =  ApartmentUser::where('apartment_id', '=', $id)->orderBy('startTerm', 'asc')->get();
        return response()->json(['mes' => null, 'data' => $reservationsOnApartment]);
    }

    //paginate()
    public function showApartment()
    {
        $apartments = Apartment::paginate(15);
        if ($apartments->getCollection()->isEmpty()) {
            return response()->json([
                'mes' => 'the apartments-list in this page is empty.',
                'data' => null
            ], 404);
        }
        $apartments->getCollection()->transform(function ($apartment) {
            $apartment->rate = $this->totalRateAccount($apartment->id); // انتبه: totalRateAccount يجب أن يرجع قيمة
            return $apartment;
        });
        return response()->json(['mes' => null, 'data' => $apartments]);
    }
    public function showOneApartment($id)
    {
        $apartment = Apartment::where('id', '=', $id)->firstOrFail();
        $apartment['rate'] = $this->totalRateAccount($id);
        return response()->json(['mes' => null, 'data' => $apartment]);
    }

    public function totalRateAccount(int $id)
    {
        $reservationsOnApartment = ApartmentUser::where('apartment_id', $id)
            ->where('enType', 'Renter')
            ->where('enStatus', 'Accepted')->get();
        $sum = 0;
        for ($i = 0; $i < count($reservationsOnApartment); $i++) {
            $sum = $sum + $reservationsOnApartment[$i]->rate;
        }
        if(count($reservationsOnApartment)==0){
            return 0;
        }
        $avg = $sum / count($reservationsOnApartment);
        return $avg;
    }
}
/*       Schema::create('apartment_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('apartment_id')->nullable()->constrained('apartments')->nullOnDelete();
            $table->enum('enType', ['Owner', 'Renter']);
            $table->enum('enStatus', ['Pending', 'Cancled', 'Accepted'])->default('Pending');
            $table->float('rate')->default(0.0);
            $table->date('startTerm')->nullable();
            $table->date('endTerm')->nullable();
            $table->timestamps();
        });*/