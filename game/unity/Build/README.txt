DROP YOUR UNITY WEBGL BUILD FILES HERE
======================================

When you build your game in Unity (File > Build Settings > WebGL > Build),
copy the contents of the generated Build/ folder into THIS folder.

Expected files:
  TowerDefense.loader.js
  TowerDefense.data.gz        (or .data.br for Brotli)
  TowerDefense.framework.js.gz
  TowerDefense.wasm.gz

Then open game/unity/index.php and update the four $build_* variables
at the top to match your actual filenames.

The page will automatically switch from the placeholder to the live game.
