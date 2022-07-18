### Issue description

- Expected result: What were you expecting to happen?
- Actual result: What actually happened?

### Steps to reproduce the issue
1. Overwrite a block and register it, overwrite the place() function by adding some code before returning the parent _for example a var_dump("hello world")_
2. In game, interact with this block in your hand with another block. Minecraft does not put the block down if the player is on the position. So place yourself on the position of the block you want to replace and right-click. **Wanted: The block is not placed and the code of the place() function is executed**

### OS and versions
<!-- try the `version` command | LATEST IS NOT A VALID VERSION -->
* PocketMine-MP: 4.6.0
* PHP: 8.0.18
* Using JIT: no
* Server OS: Win
* Game version: Android/iOS/Win10/Xbox/PS4/Switch

### Plugins
<!--- use the `plugins` command and paste the output below -->

- If you remove all plugins, does the issue still occur? yes
- If the issue is **not** reproducible without plugins: probably not
  - Have you asked for help on our forums before creating an issue? I asked several developers before
  - Can you provide sample, *minimal* reproducing code for the issue? If so, paste it in the bottom section
```PHP
Main class: 
protected function onEnable(): void
    {
        BlockFactory::getInstance()->register(new CaveBlock(new BlockIdentifier(1, 0), "Any Name", new BlockBreakInfo(20)), true);
    }
````
Block overwrite class:
```PHP
class TestBlock extends Block
{
    public function place(BlockTransaction $tx, Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, ?Player $player = null): bool
    {
        var_dump("Hello World");
        return parent::place($tx, $item, $blockReplace, $blockClicked, $face, $clickVector, $player);
    }
}
```
### Crashdump, backtrace or other files
<!--- Submit crashdumps at https://crash.pmmp.io and paste a link --> no crash
<!--- Use gist or anything else to add other files and add links here -->
