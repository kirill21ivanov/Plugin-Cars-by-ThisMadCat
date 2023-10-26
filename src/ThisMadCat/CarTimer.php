<?php

namespace ThisMadCat;

use pocketmine\scheduler\Task;
use ThisMadCat\Car;

Class CarTimer extends Task {

  private $p, $time;

  public function __construct(Car $plugin) {
    $this->p = $plugin;
  }

  public function onRun(int $currentTick): void{


    $this->time++;
    if ($this->time == 60) {
      $data = $this->p->players->getAll();
      foreach ($this->p->getServer()->getOnlinePlayers() as $player) {
        if ($data[$player->getName()]['fuel'] > 0 && isset($this->p->inCar[$player->getName()])) {
          $data[$player->getName()]['fuel']--;
          $this->p->players->setAll($data);
          $this->p->players->save();
        }
      }
    }
  }

}
