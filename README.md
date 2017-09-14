# WallCal

## Summary
WallCal is an Android app that allows the user to take a image of wall posters (traditionally found in a college setting). The user will then get an email with an attached .ical file corresponding to the times, places, and details of the events found on the posters.

## How it Works
The Android app takes the picture, saves a copy in the phone, and sends a 64-bit hash of the image to a PHP server. The server uses openCV to isolate individual posters, then uses the Google Cloud Services OCR function to find text in the poster. The text is organized into times, places, and details on the poster and the server sends a email with a .ical attachment to the user.

## Challenges
An initial idea was to use the Google Calendar API to add events automatically, but it turned out to be extrodinarily difficult combining the Google Authentication system with the camera app. In addition, the OCR will only work on high-resolution photos and the OpenCV relies on posters being relatively separated.

This project was made for the 2017 HackCMU by
Blair Chen (CMU SCS Class of 2021), Shane Guan (CMU CIT Class of 2021), Jessica Lee (CMU SCS Class of 2021), and William Zhang (CMU CIT Class of 2021).
