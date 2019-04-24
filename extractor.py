#### This script receives a given absolute folder path and returns all the files
#### 
####

import os
import sys

inputPath = raw_input("Please type the path to check: "+'\n')
for path, subdirs, files in os.walk(inputPath):
   for name in files:
        fileToParse = os.path.join(path, name)
        print os.path.join(path, name)