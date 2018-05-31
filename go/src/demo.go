package main

import ("fmt"
	"impala"
	"strings"
	"strconv")

type ReorderService struct {

}

var URL = "myurl"

func (service ReorderService) Done(payload impala.Payload) {
	input := impala.Payload{}
	_, reorder := payload.Filters["reorder"]
	_, filters := payload.Filters["producers_id"]
	fmt.Print(payload, "\n")
	if false == reorder && true == filters {
		payload.Filters["reorder"] = "clicked"
		input.Filters = payload.Filters
		input.Sort = []string{}
		impala.Grid{}.Inject(input, service, URL).Prepare()
	} else if _, exist := payload.Data["price_purchase_czk"]; exist {
		id := strconv.Itoa(int(payload.Data["fc_reorders_id"].(float64)))
		url := strings.Join([]string{URL, "default?id=", id}, "")
		impala.Grid{}.Inject(input, service, url).Prepare()
	} else if _, exist := payload.Data["total"]; exist {
		id := strconv.Itoa(int(payload.Data["fc_reorders_id"].(float64)))
		url := strings.Join([]string{URL, "submit?id=", id}, "")
		impala.Grid{}.Inject(input, service, url).Prepare()
	} else {
		fmt.Print("done\n")
	}
}

func main() {
	input := impala.Payload{}
	input.Filters = map[string]interface{}{"producers_id":[]string{"_133"}}
	input.Sort = []string{}
	var service impala.IProcess = &ReorderService{}
	input.Status = "service"
	impala.Grid{}.Inject(input, service, URL).Prepare()
}

func (service ReorderService) Run(payload impala.Payload) {

}

func (service ReorderService) Prepare(payload impala.Payload) {

}
