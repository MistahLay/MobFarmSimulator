<?php
namespace Lay\MobFarmSimulator;

use Lay\MobFarmSimulator\Simulation\Mob;
use Lay\MobFarmSimulator\Simulation\Spawner;
use Lay\MobFarmSimulator\Simulation\SpawnerGUI;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\block\Block;
use pocketmine\block\MonsterSpawner;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\SpawnEgg;
use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat;

final class EventsHandler implements Listener {

    public function __construct() {}
    
    public function onBlockInteract(){}

    public function onBlockPlace(BlockPlaceEvent $e){
        if(!$e->getItem()->getBlock() instanceof MonsterSpawner) return;
        /** @var Block $block */
        $current = $e->getTransaction()->getBlocks()->current();
        $block = $current[3];
        $data = new Spawner($e->getPlayer()->getXuid());
        MobFarmSimulator::$manager->get($block->getPosition()->getWorld())->setBlockDataAt($current[0], $current[1], $current[2], $data);
    }

    public function onPlayerInteract(PlayerInteractEvent $e){
        if($e->getAction() == PlayerInteractEvent::RIGHT_CLICK_BLOCK){
            $block = $e->getBlock();
            if(!$block instanceof MonsterSpawner) return;
            $player = $e->getPlayer();
            $pos = $block->getPosition();
            /** @var Spawner $data */
            $data = MobFarmSimulator::$manager->get($pos->getWorld())->getBlockDataAt($pos->x, $pos->y, $pos->z);
            if(!$data instanceof Spawner) return $e->cancel();
            if(!($player->getXuid() == $data->getOwner())) return $player->sendMessage("You're not the Owner!!");
            if(!isset($data)) return $player->sendMessage("Unknown Data");
            $itemUsed = $player->getInventory()->getItemInHand();
            $mobMultiplier = $data->getMobMultiplier();
            $mob = $data->getMob();
            if($itemUsed instanceof SpawnEgg){
                switch ($data->addMob($itemUsed->getTypeId())) {
                    case Spawner::REPLACED_MOB:
                        $overflow = $player->getInventory()->addItem(Mob::getMob($mob)->getEgg()->setCount($mobMultiplier));
                        foreach ($overflow as $item) {
                            $pos->getWorld()->dropItem(new Vector3($pos->x, ++$pos->y, $pos->z), $item);
                        }
                    case Spawner::ADDED_NEW_MOB:
                        $player->sendMessage("Set spawner mob to " . Mob::getMob($itemUsed->getTypeId())->getName());
                        $player->getInventory()->removeItem($itemUsed->setCount(1));
                        break;
                    case Spawner::INCREASED_MOB_LEVEL:
                        $player->sendMessage("Max Spawned Mobs increased to " . $data->getMobMultiplier());
                        $player->getInventory()->removeItem($itemUsed->setCount(1));
                        break;
                    case Spawner::UNREGISTERED_EGG:
                        $player->sendMessage("Unknown Spawn Egg");
                        break;
                    default:
                        $player->sendMessage("Mob Spawns Reached: " . $data->getMobMultiplier());
                }
                return $e->cancel();
            }
            if($itemUsed->getBlock() instanceof MonsterSpawner){
                if($data->addSpawnerLevel(1)){
                    $player->getInventory()->removeItem($itemUsed->setCount(1));
                    $player->sendMessage("Spawner Level Increased to " . $data->getSpawnerLevel());
                }else{
                    $player->sendMessage("Spawner Level Maxed: " . $data->getSpawnerLevel());
                }
                return $e->cancel();
            }
            SpawnerGUI::openSpawner($player, $pos);
            $e->cancel();
        }
    }

    public function onBlockBreak(BlockBreakEvent $e){
        $block = $e->getBlock();
        if($block instanceof MonsterSpawner){
            $player = $e->getPlayer();
            $position = $block->getPosition();
            $spawner = MobFarmSimulator::$manager->get($position->world)->getBlockDataAt($position->x, $position->y, $position->z);
            if(!$spawner instanceof Spawner) return;
            if(!($player->getXuid() == $spawner->getOwner())){
                $player->sendMessage(TextFormat::RED . "You don't own the spawner");
                $e->cancel();
                return;
            }
            $overflow = [];
            if($spawner->getMobMultiplier() >= 1){
                $overflow = array_merge($overflow, $player->getInventory()->addItem(Mob::getMob($spawner->getMob())->getEgg()->setCount($spawner->getMobMultiplier())));
            }
            $overflow = array_merge($overflow, $player->getInventory()->addItem(VanillaBlocks::MONSTER_SPAWNER()->getPickedItem()->setCount($spawner->getSpawnerLevel())));
            $e->setXpDropAmount(0);
            $e->setDrops($overflow);
        }
    }
}