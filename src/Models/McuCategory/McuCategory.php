<?php

namespace Aibnuhibban\ModuleMcu\Models\McuCategory;

use Zahzah\LaravelSupport\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class McuCategory extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'id','name'
    ];

    protected static function booted(): void{
        parent::booted();
        static::created(function($query){
            $query->service()->firstOrCreate([
                'name' => $query->name
            ]);
        });
    }

    //EIGER SECTION
    public function service(){
        return $this->morphOneModel('Service','reference');
    }
    
    //END EIGER SECTION
}
