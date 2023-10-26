<?php
namespace ThisMadCat;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\entity\Effect;
use pocketmine\utils\Utils;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\Config;

 class Car extends PluginBase implements Listener{

  public $players, $eco, $inCar, $setting, $subjects, $tag;

      public $form;

  function onEnable() {
    $folder = $this->getDataFolder();
    if (!is_dir($folder)) {
      @mkdir($folder);
      $this->saveResource('config.yml');
      $this->saveResource('players.yml');
    }
    $this->getLogger()->info('Plugin Cars by ThisMadCat');
    $this->eco = $this->getServer()->getPluginManager()->getPlugin('EconomyAPI');
    $this->getServer()->getPluginManager()->registerEvents($this, $this);
    $this->players = new Config($folder.'players.yml', Config::YAML);
    $this->getScheduler()->scheduleRepeatingTask(new CarTimer($this), 20);
    $this->form = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
  }

  public function onCommand(CommandSender $sender, Command $command, String $label, array $args) :bool{
    if ($command->getName() == 'cars') {
      if ($sender->isOp()) {
        if (count($args) == 3 or count($args) == 2) {
          if ($args[0] == 'setfuel') {
            $price = $args[1];
            if (is_numeric($price) && $price > 0) {
              $sender->sendMessage('Нажми на блок, возле которого будет покупаться бензин.');
              $this->subjects[$sender->getName()]['price'] = $price;
              $this->setting[$sender->getName()] = 2;
            } else{ $sender->sendMessage('Введи цену за 1 литр.'); return false;}
          }
        } else{ $sender->sendMessage('Использование: /cars setfuel <цена за 1 литр>'); return false;}
      } else{ $sender->sendMessage('Доступно только ОПераторам сервера.'); return false;}
    return false;}

    if ($command->getName() == 'car') {
      $car = $this->players->get($sender->getName())['car'];
      if ($car > 0) {
        if (!isset($this->inCar[$sender->getName()])) {
          $sender->sendMessage('§7Ты завёл автомобиль §3'.$car.'-го§7 уровня.');
          $this->inCar[$sender->getName()] = true;
          $sender->addEffect(new \pocketmine\entity\EffectInstance(Effect::getEffect(1), 20 * 9999,$car + 1));
          $this->tag[$sender->getName()] = $sender->getNameTag();
          $sender->setNameTag('(В автомобиле) '.$sender->getName());
        } else {
          $sender->sendMessage('§7Ты заглушил автомобиль §3'.$car.'-го§7 уровня.');
          $this->inCar[$sender->getName()] = NULL;
          $sender->removeEffect(Effect::getEffect(1)->getId());
          $sender->setNameTag($this->tag[$sender->getName()]);
          return false;
        }
      } else {$sender->sendMessage('§7У тебя нету автомобиля.'); return false;}
    return false;}

    if ($command->getName() == 'fuel') {
      $car = $this->players->get($sender->getName());
      if ($car['car'] > 0) {
        if (count($args) == 1) {
          $count = $args[0];
          if (is_numeric($count)) {
            if ($count > 0) {
              $data = $this->getConfig()->getAll();
              $price = $count * $data['fuel']['price'];
              if ($this->eco->myMoney($sender) >= $price) {
                $pdata = $this->players->getAll();
                if (($pdata[$sender->getName()]['fuel'] + $count) <= 50) {
                  $x = $sender->getFloorX() - $data['fuel']['x'];
                  $y = $sender->getFloorY() - $data['fuel']['y'];
                  $z = $sender->getFloorZ() - $data['fuel']['z'];
                  if ($x < 10 && $y < 10 && $z < 10 && $x > -10 && $y > -10 && $z > -10) {
                    $pdata[$sender->getName()]['fuel'] += $count;
                    $this->players->setAll($pdata);
                    $this->players->save();
                    $this->eco->reduceMoney($player, $price);
                    $sender->sendMessage('Ты купил '.$count.' литров бензина за $'.$price);
                  } else {$sender->sendMessage('Ты слишком далеко от заправки.'); return false;}
                } else {$sender->sendMessage('Ты не можеш заполнить бак на более чем 50 литров.'); return false;}
              } else {$sender->sendMessage('У тебя не достаточно денег.'); return false;}
            } else {$sender->sendMessage('Ты шо, ебобо? Нельзя ниже 0.'); return false;}
          } else {$sender->sendMessage('Введи кол-во литров.'); return false;}
        } else {$sender->sendMessage('/fuel <кол-во литров>'); return false;}
      } else {$sender->sendMessage('У тебя нету автомобиля.'); return false;}
    }
  return false;}

  function onTap(PlayerInteractEvent $event) {
    $player = $event->getPlayer();
    $x = $event->getBlock()->getFloorX();
    $y = $event->getBlock()->getFloorY();
    $z = $event->getBlock()->getFloorZ();
    $data = $this->getConfig()->getAll();
    if (isset($this->setting[$player->getName()])) {
      if ($this->setting[$player->getName()] == 2) {
        $player->sendMessage('Ты обозначил место заправки. Цена одного литра - $'.$this->subjects[$player->getName()]['price']);
        $data['fuel']['price'] = $this->subjects[$player->getName()]['price'];
        $data['fuel']['x'] = $x;
        $data['fuel']['y'] = $y;
        $data['fuel']['z'] = $z;
        $this->getConfig()->setAll($data);
        $this->getConfig()->save();
        unset($this->subjects[$player->getName()], $this->setting[$player->getName()]);
      }
    }
  }

  function onMove(PlayerMoveEvent $event) {
    $player = $event->getPlayer();
    $data = $this->players->getAll();
    if (isset($this->inCar[$player->getName()]) && $data[$player->getName()]['fuel'] == 0) {
      $player->sendTitle('Нету бензина.');
      $event->setCancelled();
    }
  }

  function onDamirLox(PlayerInteractEvent $event) {
    $x = $event->getBlock()->getFloorX();
    $y = $event->getBlock()->getFloorY();
    $p = $event->getPlayer();
    $z = $event->getBlock()->getFloorZ();
    if ($x == 462 && $y == 67 && $z == 326){
      $this->opens($p);
    }
  }

  public function opens(Player $pl){
    $f = $this->form->createSimpleForm(function (Player $pl, $data){
      if($data !== NULL){
            switch($data){
              case 0:
                $login = new CarForm($this);
                $login->open($pl);
                  break;
              case 1:
                $login = new CarForm2($this);
                $login->open($pl);
                  break;
              case 2:
                $login = new CarForm3($this);
                $login->open($pl);
                  break;
              case 3:
                $login = new CarForm4($this);
                $login->open($pl);
                 break;
              case 4:
                $pl->sendMessage("§7Вы §3успешно§7 вышли.");
                  break;
            }
      }
        });
        $f->setTitle("Покупка авто");
        $f->addButton("Шестёрка - 200k");
        $f->addButton("Lada - 250k");
        $f->addButton("BMW - 300k");
        $f->addButton("Mersedes - 400k");
        $f->addButton("§l§cВыход");
        $f->sendToPlayer($pl);
        return $f;
  }

  function onPreLogin(PlayerPreLoginEvent $event) {
    $player = $event->getPlayer();
    $data = $this->players->getAll();
    if (!isset($data[$player->getName()])) {
      $data[$player->getName()]['car'] = 0;
      $data[$player->getName()]['fuel'] = 0;
      $this->players->setAll($data);
      $this->players->save();
    }
  }

  function onRespawn(PlayerRespawnEvent $event) {
    $player = $event->getPlayer();
    if (isset($this->inCar[$player->getName()]))
    unset($this->inCar[$player->getName()]);
  }

}
