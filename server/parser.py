import datefinder
import sys

inputArgs = sys.argv
if len(inputArgs) == 0:
    exit()

inputArgs.pop(0)
inputDate = ''.join(inputArgs)
matches = datefinder.find_dates(inputDate)

for match in matches:
    print match
    exit()
