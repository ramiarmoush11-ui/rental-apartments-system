<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class offerApartmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type'=>'prohibited',
            'rate'=>'prohibited',
            'startTerm'=>'date|after_or_equal:today',
            'endTerm'=>'date|after_or_equal:startTerm',
        ];
    }
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