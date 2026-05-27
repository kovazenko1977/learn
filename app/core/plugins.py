import importlib
import os
import sys
from typing import List, Any

class PluginBase:
    def initialize(self, app: Any):
        pass

class PluginManager:
    def __init__(self, plugin_dir: str):
        self.plugin_dir = plugin_dir
        self.plugins: List[PluginBase] = []

    def load_plugins(self, app: Any):
        if not os.path.exists(self.plugin_dir):
            return

        sys.path.append(self.plugin_dir)
        for filename in os.listdir(self.plugin_dir):
            if filename.endswith(".py") and not filename.startswith("__"):
                module_name = filename[:-3]
                module = importlib.import_module(module_name)
                for item_name in dir(module):
                    item = getattr(module, item_name)
                    if (isinstance(item, type) and
                        issubclass(item, PluginBase) and
                        item is not PluginBase):
                        plugin = item()
                        plugin.initialize(app)
                        self.plugins.append(plugin)
                        print(f"Loaded plugin: {module_name}")
