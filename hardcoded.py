#### This script receives a given absolute folder path and returns if http:// references
#### were found into any files.
####

import os
import sys

inputPath = raw_input("Please type the path to check: "+'\n')
for path, subdirs, files in os.walk(inputPath):
   for name in files:
        fileToParse = os.path.join(path, name)
        content = open(fileToParse).read(1000)
        if content.find("http://") is not -1:
            print '\n'+"Hardcoded URL Found on:"
            print os.path.join(path, name)
        else:
            print '\n'+ "NO Hardcoded URL Found :)"