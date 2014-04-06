//
//  XNCViewController.h
//  ZhengMei
//
//  Created by devctang on 3/26/14.
//  Copyright (c) 2014 XNC Studio. All rights reserved.
//




#import "EGORefreshTableHeaderView.h"

@interface XNCViewController : UITableViewController  <EGORefreshTableHeaderDelegate, UITableViewDelegate, UITableViewDataSource>{
	
	EGORefreshTableHeaderView *_refreshHeaderView;
	
	//  Reloading var should really be your tableviews datasource
	//  Putting it here for demo purposes
	BOOL _reloading;
}

- (void)reloadTableViewDataSource;
- (void)doneLoadingTableViewData;
@end
