#!/bin/bash

systemctl is-active mysql;
if [ $? == 0 ]
then
	echo "Bezi";
else
	sudo reboot;
fi

date

