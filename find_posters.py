import numpy as np
import cv2
import argparse
import datetime


# construct the argument parse and parse the arguments
ap = argparse.ArgumentParser()
ap.add_argument("-o", "--output", required=True,
	help="path to the output directory")
ap.add_argument("-i", "--input", required=True,
	help="path to the input image")
args = vars(ap.parse_args())
print args

image = cv2.imread(args['input'])
gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
gray = cv2.GaussianBlur(gray, (3, 3), 0)

edged = cv2.Canny(gray, 10, 250)

kernel = cv2.getStructuringElement(cv2.MORPH_RECT, (10, 10))
closed = cv2.morphologyEx(edged, cv2.MORPH_CLOSE, kernel)


# find contours (i.e. the 'outlines') in the image and initialize the
# total number of books found
_, cnts, _ = cv2.findContours(closed.copy(), cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
total = 0
images = []
# loop over the contours
for c in cnts:
    # approximate the contour
    peri = cv2.arcLength(c, True)
    approx = cv2.approxPolyDP(c, 0.02 * peri, True)

    # if the approximated contour has four points, then assume that the
    # contour is a book -- a book is a rectangle and thus has four vertices
    if len(approx) == 4:

        #cv2.drawContours(image, [approx], -1, (0, 255, 0), 4)
        total += 1
        x, y, w, h = cv2.boundingRect(c)
        crop_img = image[y:y + h, x:x + w]
        images.append(crop_img)
        #y:y + h, x:x + w


# display the output
print "I found {0} posters in that image".format(total)
for im in images:
    cv2.imshow("Output", im)
    cv2.waitKey(0)
    cv2.imwrite(args['output'] + "im" + str(datetime.datetime.now().isoformat()) + ".jpg", im);
