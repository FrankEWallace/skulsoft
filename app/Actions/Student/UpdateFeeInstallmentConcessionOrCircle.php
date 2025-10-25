<?php

namespace App\Actions\Student;

use App\Enums\Finance\DefaultFeeHead;
use App\Models\Finance\FeeConcession;
use App\Models\Student\Fee as StudentFee;
use App\Models\Student\FeeRecord;
use App\Models\Transport\Circle;

class UpdateFeeInstallmentConcessionOrCircle
{
    public function execute(StudentFee $studentFee, ?FeeConcession $feeConcession, ?Circle $transportCircle): void
    {
        $feeInstallment = $studentFee->installment;

        $studentFee->fee_concession_id = $feeConcession?->id;
        $studentFee->transport_circle_id = $transportCircle?->id;

        $overpaid = 0;
        foreach ($studentFee->records as $studentFeeRecord) {
            $concessionAmount = 0;

            if ($studentFeeRecord->fee_head_id) {
                $concessionAmount = (new CalculateFeeConcession)->execute(
                    feeConcession: $feeConcession,
                    feeHeadId: $studentFeeRecord->fee_head_id,
                    amount: $studentFeeRecord->amount->value
                );
            }

            if ($studentFeeRecord->amount->value <= 0 && $concessionAmount <= 0) {
                $studentFeeRecord->delete();

                continue;
            }

            if ($studentFeeRecord->paid->value > ($studentFeeRecord->amount->value - $concessionAmount)) {
                $overpaid++;

                continue;
            }

            $studentFeeRecord->concession = $concessionAmount;
            $studentFeeRecord->save();
        }

        if ($overpaid > 0) {
            return;
        }

        if ($transportCircle) {
            $transportFeeAmount = (new GetTransportFeeAmount)->execute(
                studentFee: $studentFee,
                feeInstallment: $feeInstallment
            );

            $transportFeeConcessionAmount = (new GetTransportConcessionFeeAmount)->execute(
                feeConcession: $feeConcession,
                transportFeeAmount: $transportFeeAmount
            );

            $studentTransportFeeRecord = FeeRecord::firstOrCreate([
                'student_fee_id' => $studentFee->id,
                'default_fee_head' => DefaultFeeHead::TRANSPORT_FEE,
            ]);
            $studentTransportFeeRecord->amount = $transportFeeAmount;
            $studentTransportFeeRecord->concession = $transportFeeConcessionAmount;

            if ($studentTransportFeeRecord->paid->value > ($transportFeeAmount - $transportFeeConcessionAmount)) {
                return;
            }

            $studentTransportFeeRecord->save();
        } else {
            FeeRecord::query()
                ->where('student_fee_id', $studentFee->id)
                ->where('default_fee_head', DefaultFeeHead::TRANSPORT_FEE)
                ->delete();
        }

        $studentFee->load('records');

        $studentFee->total = $studentFee->getInstallmentTotal()->value;
        $studentFee->save();
    }
}
