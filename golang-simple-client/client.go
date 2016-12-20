package main

import (
	"bytes"
	"crypto/md5"
	"encoding/json"
	"flag"
	"fmt"
	"io"
	"io/ioutil"
	"log"
	"net/http"
	"time"
)

type (
	req struct {
		Action     string `json:"action"`
		AuthMethod string `json:"authMethod"`
		Uid        string `json:"uid"`
		Username   string `json:"username,omitempty"`
		Email      string `json:"email,omitempty"`
		Ip_addr    string `json:"ip_addr,omitempty"`
		Evidence   string `json:"evidence,omitempty"`
		Sign       string `json:"sign"`
	}
)

var (
	err error

	uid    = flag.String("uid", "", "Usage: -uid=API_ID")
	passwd = flag.String("passwd", "", "Usage: -passwd=API_PASSWD")
	action = flag.String("action", "check", "Usage: -action=<check|insert>")

	username = flag.String("username", "", "Usage: [-username=\"User Name\"]")
	email    = flag.String("email", "", "Usage: [-email=\"ovsem@pisem.net\"]")
	ip_addr  = flag.String("ip_addr", "", "Usage: [-ip_addr=127.0.0.1]")
	evidence = flag.String("evidence", "Spam messages", "Usage: [-evidence=\"Spam messages\"]")
)

const (
	api_url = "http://stopforumspam.subnets.ru/api/query.php"
)

func main() {
	flag.Parse()

	if *passwd == "" || *uid == "" {
		flag.PrintDefaults()
		log.Fatal()
	}

	switch *action {
	case "check":
		check()
	case "insert":
		n, err := check()
		if err != nil {
			log.Fatal(err)
		}
		if n < 3 {
			err = insert()
			if err != nil {
				log.Fatal(err)
			}
			check()
		}
	default:
		flag.PrintDefaults()
		log.Fatal()
	}
}

func check() (int, error) {
	if *username == "" && *email == "" && *ip_addr == "" {
		flag.PrintDefaults()
		log.Fatal()
	}

	var param = []string{"check", "md5"}
	if *email != "" {
		param = append(param, *email)
	}
	if *ip_addr != "" {
		param = append(param, *ip_addr)
	}
	param = append(param, *uid)
	if *username != "" {
		param = append(param, *username)
	}

	sign := getSign(param)

	var tmp = req{
		Action:     "check",
		AuthMethod: "md5",
		Uid:        *uid,
		Username:   *username,
		Email:      *email,
		Ip_addr:    *ip_addr,
		Sign:       sign,
	}

	body, err := json.Marshal(&tmp)
	if err != nil {
		log.Fatal(err)
	}

	contents, err := request(api_url, bytes.NewReader(body))
	if err != nil {
		log.Fatal(err)
	}
	log.Printf("Check response content: %s\n", contents)

	return checkResponce(contents)
}

func insert() error {
	if *username == "" || *email == "" || *ip_addr == "" {
		flag.PrintDefaults()
		log.Fatal()
	}

	var param = []string{"insert", "md5"}
	if *email != "" {
		param = append(param, *email)
	}
	if *evidence != "" {
		param = append(param, *evidence)
	}
	if *ip_addr != "" {
		param = append(param, *ip_addr)
	}
	param = append(param, *uid)
	if *username != "" {
		param = append(param, *username)
	}

	sign := getSign(param)

	var tmp = req{
		Action:     "insert",
		AuthMethod: "md5",
		Uid:        *uid,
		Username:   *username,
		Email:      *email,
		Ip_addr:    *ip_addr,
		Evidence:   *evidence,
		Sign:       sign,
	}

	body, err := json.Marshal(&tmp)
	if err != nil {
		log.Fatal(err)
	}

	contents, err := request(api_url, bytes.NewReader(body))
	if err != nil {
		log.Fatal(err)
	}
	log.Printf("Insert response content: %s\n", contents)

	_, err = checkResponce(contents)
	return err
}

func getSign(param []string) string {
	data := ""
	for i, v := range param {
		if i > 0 {
			v = ";" + v
		}
		data += v
	}
	data += ";" + *passwd
	return fmt.Sprintf("%x", md5.Sum([]byte(data)))
}

func checkResponce(data []byte) (int, error) {
	type response struct {
		Error            int    `json:"error"`
		Rows             int    `json:"rows"`
		ErrorDescription string `json:"errorDescription"`
	}
	var resp response
	err = json.Unmarshal(data, &resp)
	if err != nil {
		log.Fatal(err)
	}
	if resp.Error > 0 {
		return 0, fmt.Errorf("%s", resp.ErrorDescription)
	}
	return resp.Rows, nil
}

func request(url string, body io.Reader) ([]byte, error) {
	httpClient := &http.Client{
		Timeout: 20 * time.Second,
		Transport: &http.Transport{
			IdleConnTimeout:     30 * time.Second,
			DisableKeepAlives:   false,
			MaxIdleConnsPerHost: 5,
		},
	}

	req, err := http.NewRequest("POST", url, body)
	if err != nil {
		return nil, err
	}
	req.Header.Add("Content-Type", "application/json")
	resp, err := httpClient.Do(req)
	if err != nil {
		return nil, err
	}
	defer resp.Body.Close()

	contents, err := ioutil.ReadAll(resp.Body)
	if err != nil {
		return nil, err
	}

	return contents, nil
}
