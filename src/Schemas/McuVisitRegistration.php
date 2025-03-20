<?php

namespace Aibnuhibban\ModuleMcu\Schemas;

use Aibnuhibban\ModuleMcu\Contracts\{
    McuCategory, McuVisitRegistration as ContractsMcuVisitRegistration
};
use Aibnuhibban\ModuleMcu\Resources\McuVisitRegistration\{
    ShowMcuVisitRegistration, ViewMcuVisitRegistration
};
use Gii\ModuleMedicService\Enums\MedicServiceFlag;
use Illuminate\Database\Eloquent\{Builder, Model};
use Zahzah\ModulePatient\{
    Schemas\VisitRegistration
};
use Zahzah\ModuleTransaction\Concerns\PaymentCalculation;

class McuVisitRegistration extends VisitRegistration implements ContractsMcuVisitRegistration {
    use PaymentCalculation;

    protected string $__entity = 'McuVisitRegistration';
    public static $mcu_visit_model;

    protected Model $__mcu_package;
    protected MOdel $__mcu_visit_examination;
    protected MOdel $__mcu_visit_registration;
    protected MOdel $__mcu_visit_patient;


    protected array $__resources = [
        'view' => ViewMcuVisitRegistration::class,
        'show' => ShowMcuVisitRegistration::class
    ];

    protected array $__cache = [
        'index' => [
            'name'     => 'mcu-registration',
            'tags'     => ['mcu-registration','mcu-registration-index'],
            'duration' => 60*12
        ]
    ];

    public function addOrChange(? array $attributes=[]): self{
        $model = $this->updateOrCreate($attributes);
        static::$mcu_visit_model = $model;
        return $this;
    }

    protected function defaultAssessmentTemplate(Model $assessment): void{

    }

    public function prepareStoreMcuVisitRegistration(? array $attributes = null): Model {
        request()->merge([
            'medic_service_id' => $this->getMedicServiceByFlag(MedicServiceFlag::MCU->value)->service->getKey()
        ]);
        $attributes ??= request()->all();
        //POLI MCU
        static::$mcu_visit_model  = parent::prepareStoreVisitRegistration($attributes);

        $visit_registrations      = static::$mcu_visit_model->childs;
        foreach ($visit_registrations as $visit_registration) {
            $visit_registration->is_mcu = true;
            $visit_registration->save();
        }

        $this->__mcu_visit_registration = $mcu_visit_registration   = &static::$mcu_visit_model;

        $this->initPatientSummary($this->appVisitExaminationSchema()->prepareStoreVisitExamination([
            $mcu_visit_registration->getForeignKey() => $mcu_visit_registration->getKey()
        ]));
        $this->__mcu_visit_patient     = $visit_patient = $mcu_visit_registration->visitPatient;
        $this->__mcu_visit_examination = $mcu_visit_examination = $mcu_visit_registration->visitExamination;
        $transaction                      = $visit_patient->transaction;
        $transaction_payment_summary      = $transaction->paymentSummary;
        $attributes['visit_patient_id']   = $visit_patient->getKey();
        $attributes['visit_patient_type'] = $visit_patient->getMorphClass();

        //NEED FOR MAKE SOME PAYMENT SUMMARY
        $mcu_package_treatment = $this->McuServicePriceModel()->with([
            'servicePrices','reference'
        ])->findOrFail($attributes['mcu_package_id']);

        $mcu_transaction              =  $mcu_package_treatment->transaction()->create();
        $mcu_transaction->parent_id   = $transaction->getKey();
        $mcu_transaction->reported_at = now();
        $mcu_transaction->save();

        $mcu_transaction->sync($mcu_package_treatment,['id','name']);

        $visit_patient->modelHasService()->firstOrCreate([
            'reference_id'   => $visit_patient->getKey(),
            'reference_type' => $visit_patient->getMorphClass()
        ],[
            'service_id' => $mcu_package_treatment->getKey()
        ]);

        $visit_patient->sync($mcu_package_treatment,['id','name']);

        $mcu_package = $mcu_package_treatment->reference;
        if (!isset($mcu_package)) throw new \Exception('Wrong mcu package id');

        $main_mcu_package_treatment = $mcu_package->treatment;

        $mcu_payment_summary = $mcu_package_treatment->paymentSummary()->firstOrCreate([
            'parent_id'      => $transaction_payment_summary->getKey(),
            'transaction_id' => $transaction->getKey()
        ]);
        $mcu_payment_summary->name = $mcu_package_treatment->name;
        $mcu_payment_summary->transaction_id = $mcu_transaction->getKey();
        $mcu_payment_summary->save();

        //LEVEL 1 SERVICE PRICES UNTUK POLY CLINIC
        $main_mcu_package_treatment->load([
            'serviceItems' => fn ($query) => $query->whereNull('parent_id')
        ]);

        unset($attributes['medic_services']);
        $mcu_package_summary = $this->getMcuPackageSummaryTemplate([
            'mcu_visit_examination_id' => $mcu_visit_examination->getKey()
        ]);
        $service_lists = &$mcu_package_summary['examination']['MCUPackageSummary']['data']['service_lists'];
        $mcu_package_summary_lab = &$service_lists[1]['child'];
        $mcu_package_summary_rad = &$service_lists[2]['child'];
        $has_mcu_package_summary = false;

        foreach ($main_mcu_package_treatment->serviceItems as $service_item) {
            $services = [];
            $service_item->load([
                'childs' => function($query) use ($mcu_package_treatment){
                    $query->whereHas('servicePrice',function($query) use ($mcu_package_treatment){
                        $query->where('service_id',$mcu_package_treatment->getKey());
                    });
                }
            ]);
            foreach ($service_item->childs as $child) {
                $services[] = [
                    'id'                        => $child->reference_id,
                    'qty'                       => 1,
                    'price'                     => $child->servicePrice->price
                ];
            }

            $medic_service      = $service_item->reference;
            $visit_registration = $this->generateVisitRegistration($mcu_visit_registration,$medic_service,$services);

            $referral_visit_examination           = $visit_registration->visitExamination;
            $visit_reg_payment_summary            = $visit_registration->paymentSummary;
            $visit_reg_payment_summary->parent_id = $transaction_payment_summary->getKey();
            $visit_reg_payment_summary->name      = 'Total tagihan '.$medic_service->name;
            if (count($services) > 0) {
                foreach ($services as $key => $service_attr) {
                    $assessment     = $this->AssessmentModel()->where('visit_examination_id',$referral_visit_examination->getKey())
                                            ->where('props->treatment_id',$service_attr)->first();
                    switch ($assessment->morph) {
                        case $this->LabTreatmentModelMorph():
                            $has_mcu_package_summary = true;
                            $mcu_package_summary_lab[] = [
                                "name"   => $assessment->name,
                                "result" => "Normal"
                            ];
                        break;
                        case $this->RadiologyTreatmentModelMorph():
                            $has_mcu_package_summary = true;
                            $mcu_package_summary_rad[] = [
                                "name"   => $assessment->name,
                                "result" => "Normal"
                            ];
                        break;
                        default :
                            $this->defaultAssessmentTemplate($assessment);
                    }

                    $payment_detail = $this->PaymentDetailModel()->where('payment_summary_id',$visit_reg_payment_summary->getKey())
                                            ->whereHas('transactionItem',function($query) use ($assessment){
                                                $query->where('item_id',$assessment->getKey())
                                                      ->where('item_type',$assessment->morph);
                                            })->first();

                    $current_service_item = $service_item->childs[$key];
                    $service_price        = $current_service_item->servicePrice()->where('service_id',$mcu_package_treatment->getKey())
                                            ->firstOrFail();
                    if (!isset($payment_detail)) throw new \Exception('Wrong payment detail: '.$assessment->name ?? '-');
                    $payment_detail->debt   = ($payment_detail->qty ?? 1) * $service_price->price;
                    $payment_detail->amount = $payment_detail->debt;
                    $payment_detail->save();

                    $previous_payment_summary = $payment_detail->paymentSummary;
                    $previous_payment_summary->total_amount -= $payment_detail->debt;
                    $previous_payment_summary->total_debt   -= $payment_detail->debt;
                    $previous_payment_summary->save();

                    $payment_detail->payment_summary_id = $mcu_payment_summary->getKey();
                    $payment_detail->save();
                }
            }
            $visit_reg_payment_summary->save();
        }

        if ($has_mcu_package_summary){
            $this->schemaContract('examination')->prepareBulkStoreExamination($mcu_package_summary);
        }
        //CREATE OTHER SERVICE
        $visit_patient->modelHasService()->updateOrCreate([
            'reference_id'   => $visit_patient->getKey(),
            'reference_type' => $visit_patient->getMorphClass()
        ],[
            'service_id' => $this->appMcuCategorySchema()
                                ->mcuCategory()->findOrFail($attributes['mcu_category_id'])
                                ->service->getKey()
        ]);

        $transaction->reported_at = now();
        $transaction->save();

        $mcu_visit_registration->prop_service_ids = $mcu_visit_registration->prop_service_labels[0]->id ?? "";
        $mcu_visit_registration->save();
        
        return $mcu_visit_registration;
    }

    protected function generateVisitRegistration(Model $mcu_visit_registration,Model $medic_service,array $services): Model{
        $store_service_attr  = [
            'visit_patient_id'                    => $mcu_visit_registration->visit_patient_id,
            'visit_patient_type'                  => $mcu_visit_registration->visit_patient_type,
            'patient_type_id'                     => $mcu_visit_registration->patient_type_id ?? null,
            'visit_registration_medic_service_id' => $mcu_visit_registration->medic_service_id,
            'visit_registration_parent_id'        => $mcu_visit_registration->getKey(),
            'medic_services' => [
                [
                    'id'       => $medic_service->getKey(),
                    'services' => $services,
                ]
            ]
        ];
        return $this->storeServices($store_service_attr)[0];
    }

    protected function getPhysicalExaminationTemplate(): array {
        return [
            [
                "name"    => "Consultation with GP",
                "result"  => "Normal"
            ],
            [
                "name"    => "Eye and Vision Examination",
                "result"  => "Normal"
            ],
            [
                "name"    => "Tone Audiogram",
                "result"  => "Normal"
            ],
            [
                "name"    => "Electrocardiogram",
                "result"  => "Normal"
            ]
        ];
    }

    protected function getMcuPackageSummaryTemplate(array $attributes): array{
        return [
            "visit_examination_id" => $attributes['mcu_visit_examination_id'],
            "examination" => [
                "MCUPackageSummary" => [
                    "data" => [
                        "abnormalities" => null,
                        "suggestions"   => null,
                        "service_lists" => [
                            [
                                "id"     => null,
                                "name"   => "Physical Examination",
                                "child" => $this->getPhysicalExaminationTemplate()
                            ],
                            [
                                "id"     => null,
                                "name"   => "Laboratory Tests",
                                "child" => []
                            ],
                            [
                                "id"     => null,
                                "name"   => "Radiology Tests",
                                "child" => []
                            ]
                            // [
                            //     "id": null,
                            //     "name": "Lab",
                            //     "child": [
                            //         {
                            //             "name": "CBC",
                            //             "result": "Normal"
                            //         },
                            //         {
                            //             "name": "TSA",
                            //             "result": "Normal"
                            //         }
                            //     ]
                            // ]
                        ]
                    ]
                ]
            ]
        ];
    }

    public function storeMcuVisitRegistration(): array{
        return $this->transaction(function(){
            return $this->showVisitRegistration($this->prepareStoreMcuVisitRegistration());
        });
    }

    public function mcuVisitRegistration(mixed $conditionals = null): Builder{
        $this->booting();
        return $this->McuVisitRegistrationModel()->conditionals($conditionals);
    }

    public function setReportTransactionVisitPatient($visit_patient) {}
}
