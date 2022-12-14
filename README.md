# Hidroponics Utility

## EcCalibrate

Калибровка по 3 точкам.

Напряжение можно указывать в mV (3300),
Скорее всего это будет также работать для штук, в таком случае `$uRef = 4095`

### Установка

```sh
composer require politsin/hydroponics
```

### Примеры использования:

```php
<?
// Сопротивление резистора делителя напряжения.
$r0 = 500;
// Напряжение на резисторе.
$uRef = 3300;
// Точки по которым делается калибровка.
$points = [
  ['u' => 2005, 'ec' => 1114],
  ['u' => 1593, 'ec' => 2132],
  ['u' => 1089, 'ec' => 4988],
];
// Инициализация.
$ecCalc = new Hydroponics\EcCalibrate($r0, $uRef);
// Рассчет коэффициентов для формулы
$coefs = $ecCalc->mathCoefs($points);
// Текущее измеренное напряжение.
$u = 1800;
// Переводим напряжение в EC.
$ec = $ecCalc->calc($u);
```

```php
<?
$r0 = 500;
$uRef = 3300;
// Заранее рассчитанные коэффициенты.
$coefs = [
  "a" => -100657508,
  "b" => 6878,
  "c" => 14657,
];
// Инициализация класса с уже известными коэффициентами.
$ecCalc = new Hydroponics\EcCalibrate($r0, $uRef, $coefs);
// Текущее измеренное напряжение.
$u = 1800;
// Переводим напряжение в EC.
$ec = $ecCalc->calc($u);
```
