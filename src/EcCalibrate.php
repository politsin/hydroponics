<?php

namespace Hydroponics;

/**
 * Ec Calibrate service.
 */
class EcCalibrate {

  /**
   * R, Ω (Voltage divider).
   *
   * @var float
   */
  public float $r0;

  /**
   * Reference Voltage, mV.
   *
   * @var float
   */
  public float $uRef;

  /**
   * Coefs.
   *
   * @var array
   */
  public array|null $coefs;

  /**
   * Resists.
   *
   * @var array
   */
  public array|null $resists;

  /**
   * Calc Results.
   *
   * @var array
   */
  public array|null $calcResults;

  /**
   * Creates a new EcCalibrate.
   *
   * @param float $r0
   *   The config factory.
   * @param float $uRef
   *   Reference Voltage.
   * @param array|null $coefs
   *   Coefs.
   */
  public function __construct(float $r0, float $uRef, array | null $coefs = NULL) {
    $this->r0 = $r0;
    $this->uRef = $uRef;
    $this->coefs = $coefs;
  }

  /**
   * Calc.
   *
   * @param float $u
   *   Voltage from ADC, mV.
   */
  public function calc(float $u) : float {
    $ecRo = $this->r0;
    $referenceVoltage = $this->uRef;
    $r = $ecRo * ($referenceVoltage - $u) / $u;
    $a = $this->coefs['a'];
    $b = $this->coefs['b'];
    $c = $this->coefs['c'];
    $ec = $a / ($r - $b) - $c;
    $this->calcResults = [
      'u' => $u,
      'r' => round($r),
      'ec' => round($ec),
    ];
    return $this->calcResults['ec'];
  }

  /**
   * Math.
   *
   * @param array $points
   *   3 of $point = ['u' => NUMBER, 'ec' => NUMBER].
   */
  public function mathCoefs(array $points) : array {
    $u1 = $points[0]['u'];
    $u2 = $points[1]['u'];
    $u3 = $points[2]['u'];
    $ec1 = $points[0]['ec'];
    $ec2 = $points[1]['ec'];
    $ec3 = $points[2]['ec'];

    $referenceVoltage = $this->uRef;
    $ecRo = $this->r0;

    $r1 = $ecRo * ($referenceVoltage - $u1) / $u1;
    $r2 = $ecRo * ($referenceVoltage - $u2) / $u2;
    $r3 = $ecRo * ($referenceVoltage - $u3) / $u3;

    $a_top =
      (pow($ec1, 2) * $ec2 - $ec1 * pow($ec2, 2) + ($ec1 - $ec2) * pow($ec3, 2) -
      (pow($ec1, 2) - pow($ec2, 2)) * $ec3) *
          pow($r1, 2) * $r2 -
          (pow($ec1, 2) * $ec2 - $ec1 * pow($ec2, 2) + ($ec1 - $ec2) * pow($ec3, 2) -
          (pow($ec1, 2) - pow($ec2, 2)) * $ec3) *
          $r1 * pow($r2, 2) +
          ((pow($ec1, 2) * $ec2 - $ec1 * pow($ec2, 2) + ($ec1 - $ec2) * pow($ec3, 2) -
          (pow($ec1, 2) - pow($ec2, 2)) * $ec3) *
          $r1 -
          (pow($ec1, 2) * $ec2 - $ec1 * pow($ec2, 2) + ($ec1 - $ec2) * pow($ec3, 2) -
          (pow($ec1, 2) - pow($ec2, 2)) * $ec3) *
          $r2) *
          pow($r3, 2) -
          ((pow($ec1, 2) * $ec2 - $ec1 * pow($ec2, 2) + ($ec1 - $ec2) * pow($ec3, 2) -
          (pow($ec1, 2) - pow($ec2, 2)) * $ec3) *
          pow($r1, 2) -
          (pow($ec1, 2) * $ec2 - $ec1 * pow($ec2, 2) + ($ec1 - $ec2) * pow($ec3, 2) -
          (pow($ec1, 2) - pow($ec2, 2)) * $ec3) *
           pow($r2, 2)) *
           $r3;
    $a_bottom =
           (pow($ec2, 2) - 2 * $ec2 * $ec3 + pow($ec3, 2)) * pow($r1, 2) -
           2 * ($ec1 * $ec2 - ($ec1 + $ec2) * $ec3 + pow($ec3, 2)) * $r1 * $r2 +
           (pow($ec1, 2) - 2 * $ec1 * $ec3 + pow($ec3, 2)) * pow($r2, 2) +
           (pow($ec1, 2) - 2 * $ec1 * $ec2 + pow($ec2, 2)) * pow($r3, 2) +
           2 *
           (($ec1 * $ec2 - pow($ec2, 2) - ($ec1 - $ec2) * $ec3) * $r1 -
           (pow($ec1, 2) - $ec1 * $ec2 - ($ec1 - $ec2) * $ec3) * $r2) *
           $r3;
    $b_top =
      ($ec1 - $ec2) * $r1 * $r2 - (($ec1 - $ec3) * $r1 - ($ec2 - $ec3) * $r2) * $r3;
    $b_bottom = ($ec2 - $ec3) * $r1 - ($ec1 - $ec3) * $r2 + ($ec1 - $ec2) * $r3;
    $c_top = ($ec1 - $ec2) * $ec3 * $r3 + ($ec1 * $ec2 - $ec1 * $ec3) * $r1 -
                 ($ec1 * $ec2 - $ec2 * $ec3) * $r2;
    $c_bottom = ($ec2 - $ec3) * $r1 - ($ec1 - $ec3) * $r2 + ($ec1 - $ec2) * $r3;
    $coefs = [
      "a" => round(-$a_top / $a_bottom, 3),
      "b" => round(-$b_top / $b_bottom, 3),
      "c" => round(-$c_top / $c_bottom, 3),
    ];
    $this->resists = [
      "r1" => round($r1),
      "r2" => round($r2),
      "r3" => round($r3),
    ];
    $this->coefs = $coefs;
    $this->fillBottles();
    return $coefs;
  }

  /**
   * Debug calc [EC => U].
   */
  private function fillBottles() : void {
    $ecRo = $this->r0;
    $referenceVoltage = $this->uRef;
    $a = $this->coefs['a'];
    $b = $this->coefs['b'];
    $c = $this->coefs['c'];
    $bottles = [
      "1114" => NULL,
      "2132" => NULL,
      "3107" => NULL,
      "4057" => NULL,
      "4988" => NULL,
      "5909" => NULL,
    ];
    foreach ($bottles as $key => $value) {
      $r = $a / ($key + $c) + $b;
      $u = $ecRo * $referenceVoltage / ($r + $ecRo);
      $bottles[$key] = round($u);
    }
    $this->bottles = $bottles;
  }

}
