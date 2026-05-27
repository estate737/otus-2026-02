<?
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Loader;
use App\Iblock\Property\DoctorProceduresProperty;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); ?>
<?php
$APPLICATION->SetTitle("ДЗ #7: Кастомный тип свойства и попап записи");
Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');
Loader::includeModule('iblock');

/**
 * ID инфоблока по коду.
 */
function hw7_iblock(string $code): int
{
    $row = CIBlock::GetList([], ['CODE' => $code])->Fetch();
    return $row ? (int) $row['ID'] : 0;
}

$doctorsIblock = hw7_iblock('doctors');
$bookingIblock = hw7_iblock('booking');

// врачи
$doctors = [];
$rs = CIBlockElement::GetList(['NAME' => 'ASC'], ['IBLOCK_ID' => $doctorsIblock, 'ACTIVE' => 'Y'], false, false, ['ID', 'NAME', 'PROPERTY_SPECIALIZATION']);
while ($row = $rs->Fetch())
{
    $doctors[] = $row;
}

// существующие брони
$bookings = [];
$rs = CIBlockElement::GetList(['ID' => 'DESC'], ['IBLOCK_ID' => $bookingIblock, 'ACTIVE' => 'Y'], false, false, ['ID', 'NAME', 'PROPERTY_BOOKING_TIME', 'PROPERTY_DOCTOR', 'PROPERTY_PROCEDURE']);
while ($row = $rs->Fetch())
{
    $bookings[] = $row;
}

// имена врачей и процедур для таблицы броней
$names = [];
$rs = CIBlockElement::GetList([], ['IBLOCK_ID' => [$doctorsIblock, hw7_iblock('procedures')]], false, false, ['ID', 'NAME']);
while ($row = $rs->Fetch())
{
    $names[$row['ID']] = $row['NAME'];
}
?>

<h1 class="mb-4"><? $APPLICATION->ShowTitle() ?></h1>

<div class="alert alert-light border mb-4">
    Создан инфоблок <code>booking</code> (Бронирование) с полями ФИО пациента, Время записи, Врач, Процедура.
    Написан пользовательский тип свойства инфоблока <code>doctor_procedures</code>
    (класс <code>App\Iblock\Property\DoctorProceduresProperty</code>), зарегистрированный через событие
    <code>OnIBlockPropertyBuildList</code>. В выводе свойства показываются процедуры выбранного врача.
    По клику на процедуру открывается <code>BX.PopupWindow</code> с формой пациента; при заполнении создаётся бронь.
    Время на занятость проверяется (доп. задание).
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">Запись на процедуру</div>
    <div class="card-body">
        <?php foreach ($doctors as $doctor): ?>
            <div class="mb-3 pb-3 border-bottom">
                <div class="fw-bold mb-2">
                    <?= htmlspecialcharsbx($doctor['NAME']) ?>
                    <small class="text-muted"><?= htmlspecialcharsbx((string) $doctor['PROPERTY_SPECIALIZATION_VALUE']) ?></small>
                </div>
                <?= DoctorProceduresProperty::renderWidget((int) $doctor['ID']) ?>
            </div>
        <?php endforeach; ?>
        <?php if (empty($doctors)): ?>
            <div class="text-muted">Нет врачей</div>
        <?php endif; ?>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
        <span>Брони</span>
        <span class="badge bg-light text-success"><?= count($bookings) ?></span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-striped mb-0">
            <thead class="table-light">
                <tr><th>ID</th><th>Пациент</th><th>Врач</th><th>Процедура</th><th>Время</th></tr>
            </thead>
            <tbody>
            <?php foreach ($bookings as $b): ?>
                <tr>
                    <td><?= (int) $b['ID'] ?></td>
                    <td><?= htmlspecialcharsbx($b['NAME']) ?></td>
                    <td><?= htmlspecialcharsbx($names[$b['PROPERTY_DOCTOR_VALUE']] ?? '') ?></td>
                    <td><?= htmlspecialcharsbx($names[$b['PROPERTY_PROCEDURE_VALUE']] ?? '') ?></td>
                    <td><?= htmlspecialcharsbx((string) $b['PROPERTY_BOOKING_TIME_VALUE']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($bookings)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">Броней пока нет</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-dark text-white">Файлы проекта</div>
    <div class="list-group list-group-flush">
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2FApp%2FIblock%2FProperty%2FDoctorProceduresProperty.php&full_src=Y" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            App/Iblock/Property/DoctorProceduresProperty.php
            <span class="badge bg-primary">класс типа свойства</span>
        </a>
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fphp_interface%2Finit.php&full_src=Y" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            php_interface/init.php
            <span class="badge bg-secondary">регистрация OnIBlockPropertyBuildList</span>
        </a>
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fjs%2Fbooking.js&full_src=Y" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            js/booking.js
            <span class="badge bg-warning text-dark">BX.PopupWindow</span>
        </a>
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Fhomeworks%2Fhomework7%2Fajax.php&full_src=Y" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            homework7/ajax.php
            <span class="badge bg-info text-dark">создание брони</span>
        </a>
    </div>
</div>

<script>
window.OtusBookingReload = function () { window.location.reload(); };
</script>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
