#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <ArduinoJson.h>

#define INPUT_PIN 14
// PIN 14 maps to D5 on esp8266

const int COUNTERMAX = 300;
bool oldInputState;
bool POWER_SAVE;
bool wifiON;
int minutesToWake = 0;
int MasterCounter = COUNTERMAX;
String myid = "xxxx";
String key = "xxxx";

void setup() {
  Serial.begin(9600);   // used to output log details =)
  // config static IP
  IPAddress ip(192, 168, 10, 51);
  IPAddress gateway(192, 168, 10, 1);
  //Serial.print("Setting static ip to : ");
  //Serial.println(ip);
  IPAddress subnet(255, 255, 255, 0);
  WiFi.config(ip, gateway, subnet);
  
  WiFi.begin("MYWIFINAME", "MYWIFIPASSWORD");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    //Serial.println("Waiting for connection");
  }
  //Serial.println(WiFi.macAddress());
  pinMode(INPUT_PIN, INPUT_PULLUP);  
  oldInputState = !digitalRead(INPUT_PIN);
  POWER_SAVE = true;
  wifiON = true;
}

void loop() {
  int inputState = digitalRead(INPUT_PIN);
  Serial.println(String(!inputState));  
  
  if (inputState != oldInputState)
  {
    WifiON();
    if(WiFi.status()== WL_CONNECTED){
       HTTPClient http;
       String temp = "http://noqueue.yetilair.com/sys/?method=sensor&id=" + myid + "&key=" + key + "&request=setStatus&status=" + String(!inputState);
       http.begin(temp);
       http.addHeader("Content-Type", "text/plain");
     
       int httpCode = http.POST("Status update from a Pooper");
       String myresponse = http.getString();
       MasterCounter = COUNTERMAX;
    
       //Serial.println(String(!inputState));
       //Serial.println(httpCode);
       //Serial.println(myresponse);
       http.end();  //Close connection
    }else{
       Serial.println("ERROR with WiFi connection");   
    }
    oldInputState = inputState;
    WifiOFF();
  }
  //Serial.println(MasterCounter);
  if(MasterCounter <= 0) {
    MasterCounter = COUNTERMAX;
    WifiON();
    CheckActiveTime();
    if(minutesToWake > 0) {
      startDeepSleep(minutesToWake);
    }
  } else {
    MasterCounter = MasterCounter - 1;
  }
  delay(5000);  // Check every 5 seconds
}

void CheckActiveTime() {
  minutesToWake = 0;
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    String temp = "http://noqueue.yetilair.com/sys/?method=sensor&id=" + myid + "&key=" + key + "&request=getSleepTime";
    //Serial.println("--check active time--");
    http.begin(temp);
    int httpCode = http.GET();
    if (httpCode > 0) {
      String myresponse = http.getString();   //Get the request response payload
      //Serial.println(myresponse);           //Print the response payload
      if(myresponse.length() > 0){
        minutesToWake = myresponse.toInt();
      }
    }
    http.end();   //Close connection
  }
}

void startDeepSleep(int sleepMinutes) {
  if(sleepMinutes > 0) {
    Serial.print("start deep sleep:");
    Serial.println(sleepMinutes);
    ESP.deepSleep(sleepMinutes * 60 * 1000000);
    //ESP.deepSleep(1 * 60 * 1000000);
  }
}

void WifiOFF()
{
  if (!POWER_SAVE) { return; }
  //Serial.println("wifi off");
  delay(100);
  WiFi.forceSleepBegin(0);
  delay(200);
  wifiON = false;
}
void WifiON()
{
  if (!POWER_SAVE || wifiON) { return; }
  //Serial.println("wifi on");
  WiFi.forceSleepWake();
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  wifiON = true;
}

