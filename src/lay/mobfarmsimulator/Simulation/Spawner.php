<?php
namespace Lay\MobFarmSimulator\Simulation;

use cosmicpe\blockdata\BlockData;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\item\Item;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;

final class Spawner implements BlockData {

    const int MAX_SPAWNER_LEVEL = 10;
    
    const int INCREASED_MOB_LEVEL = 2;
    
    const int MAX_XP = 100;
    
    const string UNKNOWN = "unknown";
    
    const int MAX_MOB_COUNT = 30;
    
    const int SPAWN_DELAY = 10;
    
    const int ADDED_NEW_MOB = 1;

    const int MAX_MULTIPLIER = 2;

    const int UNREGISTERED_EGG = 3;

    const int REPLACED_MOB = 4;

    private string $owner = self::UNKNOWN;

    private int $level = 1;

    private string $mob = self::UNKNOWN;

    private int $mob_multiplier = 0;

    private int $activationTime = 0;

    private int $xp = 0;

    /** @var int[] */
    private array $chestPouch = [100, 200, 300];

    public function __construct(string $owner){
        $this->owner = $owner;
    }

    public static function nbtDeserialize(CompoundTag $nbt): BlockData{
        $object = new self($nbt->getString("owner"));
        $object->xp = $nbt->getInt("xp");
        $object->mob = $nbt->getString("mob");
        $object->level = $nbt->getInt("level");
        $object->mob_multiplier = $nbt->getInt("mob_multiplier");
        $object->activationTime = $nbt->getInt("activation_time");
        if(!($object->mob == self::UNKNOWN)){
            foreach ($nbt->getListTag("chest_pouch") as $key => $value) {
                $object->chestPouch[$key] = $value->getValue();
            }
        }
        return $object;
    }

    public function nbtSerialize(): CompoundTag{
        $tag = CompoundTag::create()
            ->setString("owner", $this->owner)
            ->setInt("xp", $this->xp)
            ->setString("mob", $this->mob)
            ->setInt("mob_multiplier", $this->mob_multiplier)
            ->setInt("level", $this->level)
            ->setInt("activation_time", $this->activationTime);
        $pouch = [];
        foreach ($this->chestPouch as $amount) {
            $pouch[] = new IntTag($amount);
        }
        $tag->setTag("chest_pouch", new ListTag($pouch));
        return $tag;
    }

    public function getOwner(){ return $this->owner; }

    public function addMob(string $mobID):int{
        if($mob = Mob::getMob($mobID)){
            if($mobID == $this->mob){
                if($this->mob_multiplier == $this->getMaxMobMultiplier()){
                    return 0;
                }
                $this->mob_multiplier++;
                return self::INCREASED_MOB_LEVEL;
            }
            $this->mob_multiplier = 1;
            $this->chestPouch = [];
            foreach ($mob->getMobDrops() as $v) {
                $this->chestPouch[] = 0;
            }
            $this->activate();
            if($this->mob == self::UNKNOWN){
                $this->mob = $mobID;
                return self::ADDED_NEW_MOB;
            }else{
                $this->mob = $mobID;
                return self::REPLACED_MOB;
            }
        }
        return self::UNREGISTERED_EGG;
    }

    public function getMob():string{ 
        return $this->mob; 
    }
    public function getMobMultiplier():int{ 
        return $this->mob_multiplier; 
    }
    public function getMaxMobMultiplier(){ 
        return self::MAX_MULTIPLIER * $this->level; 
    }

    public function addXp(int $amount){ 
        $xp = $this->xp + $amount; 
        if($xp > $this->getMaxXp()){
            $this->xp = $this->getMaxXp();
            return;
        }
        $this->xp = $xp;
    }
    public function setXp(int $amount){ 
        if($amount < 0 && !($amount > $this->getMaxXp())) return;
        $this->xp = $amount;
    }
    public function getXp(){ 
        return $this->xp; 
    }
    public function getMaxXp(){ 
        return self::MAX_XP * $this->level; }


    public function setSpawnerLevel(int $level):int {  
        if($level >= 0 && $level >= self::MAX_SPAWNER_LEVEL){
            $this->level = $level;
            return true;
        }
        return false;
    }
    public function addSpawnerLevel(int $level){
        if($level >= 0 && ($this->level + $level) <= self::MAX_SPAWNER_LEVEL){
            $this->level += $level;
            return true;
        }
        return false;
    }
    public function getSpawnerLevel(){ 
        return $this->level; 
    }

    public function getActivationTimeStamp(){ 
        return $this->activationTime; 
    }

    // Execute on block load
    public function activate(){ 
        return $this->activationTime = time(); 
    }
    public function getMaxMobs(){ return self::MAX_MOB_COUNT * $this->level; }

    public function getSpawnedMobs(): ?int{
        if($this->mob == self::UNKNOWN) return null;
        $total = round((time() - $this->activationTime) / self::SPAWN_DELAY);
        $maxMobs = $this->getMaxMobs();
        return $total >= $maxMobs ? $maxMobs : $total;
    }

    public function generateDrops(){
        $mob = Mob::getMob($this->mob);
        foreach($mob->createDrops($this->getSpawnedMobs()) as $key => $item){
            if($key == "xp"){
                $this->addXp($item);
                continue;
            }
            /**
             * @var Item $item
             */
            $this->chestPouch[$key]+=$item->getCount();
        }
        $this->activate();
    }

    public function getChestPouch(){
        return $this->chestPouch;
    }

    public function setChestPouch(array $items){
        $this->chestPouch = $items;
    }
}