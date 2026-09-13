<?php

namespace App\Handler;

use App\Service\WorkOrderAccessService;

/**
 * Открывает ответственному за заказ-наряд клиента и автомобиль.
 *
 * @package App\Handler
 */
class WorkOrderAccessHandler
{
    /**
     * Обработчик событий crm:OnAfterCrmDealAdd и crm:OnAfterCrmDealUpdate.
     *
     * @param array $fields поля сделки (содержат ID)
     * @return void
     */
    public static function onSave($fields): void
    {
        $dealId = is_array($fields) ? (int) ($fields['ID'] ?? 0) : 0;
        if ($dealId <= 0) {
            return;
        }

        // сотрудник, сохранивший заказ-наряд; у агентов и скриптов его нет
        $grantorId = ($GLOBALS['USER'] ?? null) instanceof \CUser ? (int) $GLOBALS['USER']->GetID() : 0;

        try {
            (new WorkOrderAccessService())->grantForDeal($dealId, $grantorId);
        } catch (\Throwable $e) {
            // ошибка выдачи доступа не должна мешать сохранению заказ-наряда
        }
    }
}
